<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Lottery;
use App\Models\PickedTicket;
use App\Models\Winner;
use App\Models\CoinType;
use App\Models\Deposit; // Added for fetching deposit info
use App\Models\UserCoinBalance;
use App\Constants\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LotteryController extends Controller {
    public function cartItems() {
        $cartItems = Cart::where('user_id', auth()->id())->get();

        if ($cartItems->isEmpty()) {
            $notify[] = ['info', 'Your cart is empty.'];
            return redirect()->route('home')->withNotify($notify); // Or to lottery listing page
        }

        $cartItemPrice = 0;
        $cartDetailsForSession = [];

        foreach ($cartItems as $cartItem) {
            $cartItemPrice += $cartItem->lottery->price * $cartItem->quantity;
            $cartDetailsForSession[] = [
                'lottery_id' => $cartItem->lottery_id,
                'quantity'   => $cartItem->quantity,
                'price_per_ticket' => $cartItem->lottery->price // Price in site currency
            ];
        }

        session()->forget('cartItemPriceTotal');
        session()->put('cartItemPriceTotal', $cartItemPrice);
        session()->put('lotteryCartDetails', $cartDetailsForSession); // Store detailed cart items

        return redirect()->route('user.deposit.index');
    }

    public function purchasedLottery() {
        $pageTitle = "My Lotteries";
        $lotteries = Lottery::pickedAndWonByUser(auth()->id())->searchable(['name'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view('Template::user.lottery.purchased', compact('pageTitle', 'lotteries'));
    }

    public function purchasedLotteryDetail(Request $request, $slug) {
        $lottery = Lottery::where('slug', $slug)->whereHas('pickedTickets')->first();

        if (!$lottery) {
            return redirect()->back()->withErrors(['error' => 'Lottery not found.']);
        }

        $pageTitle = $lottery->name;

        $pickedTickets = PickedTicket::where('lottery_id', $lottery->id)->where('user_id', auth()->id())->get();
        $totalPrice    = @$pickedTickets->sum('price');

        $allPickedTickets = $pickedTickets->pluck('choosen_tickets')->toArray();
        $pickedTickets    = array_merge(...$allPickedTickets);
        sort($pickedTickets);

        $winningTickets    = Winner::where('lottery_id', $lottery->id)->where('user_id', auth()->id())->get();
        $allWinningTickets = [];
        foreach ($winningTickets as $pickedTicket) {
            $allWinningTickets[] = $pickedTicket->ticket_number;
        }
        sort($allWinningTickets);
        return view('Template::user.lottery.purchased_detail', compact('pageTitle', 'lottery', 'pickedTickets', 'allWinningTickets', 'totalPrice'));
    }

    public function purchaseCartWithCoins(Request $request)
    {
        $user = auth()->user();
        $cartItems = Cart::where('user_id', $user->id)->with('lottery')->get();

        if ($cartItems->isEmpty()) {
            $notify[] = ['error', 'Your cart is empty.'];
            return redirect()->route('home')->withNotify($notify); // Or back to cart page
        }

        $totalPriceSiteCurrency = $cartItems->sum(function ($item) {
            return $item->calculateSingleCartPrice();
        });

        $baseCoin = CoinType::getBaseCoin();
        if (!$baseCoin) {
            $notify[] = ['error', 'Base coin is not configured. Cannot proceed with coin payment.'];
            return redirect()->route('cart')->withNotify($notify);
        }

        // --- Convert total price to base coin value ---
        $siteCurrencyCode = strtoupper(gs('cur_text'));
        $totalPriceInBaseCoin = 0;

        if ($siteCurrencyCode == strtoupper($baseCoin->code)) {
            $totalPriceInBaseCoin = $totalPriceSiteCurrency;
        } else {
            $siteCurrencyAsCoinType = CoinType::where('code', $siteCurrencyCode)->active()->first();
            if ($siteCurrencyAsCoinType) {
                $totalPriceInBaseCoin = $siteCurrencyAsCoinType->convertToBaseCoin($totalPriceSiteCurrency);
            } else {
                $notify[] = ['error', 'Currency conversion to base coin failed for site currency. Please contact support or use another payment method.'];
                \Log::error("LotteryCoinPurchase: Failed to find site currency {$siteCurrencyCode} as a CoinType for conversion to base coin {$baseCoin->code} for user {$user->id}, amount {$totalPriceSiteCurrency}");
                return redirect()->route('cart')->withNotify($notify);
            }
        }
        // --- End Conversion ---

        $userBaseCoinBalanceInstance = UserCoinBalance::getOrCreateBalance($user->id, $baseCoin->id);

        if ((float)$userBaseCoinBalanceInstance->balance < (float)$totalPriceInBaseCoin) {
            $notify[] = ['error', 'Insufficient ' . __($baseCoin->name) . ' balance to complete the purchase. Required: '.showAmount($totalPriceInBaseCoin, $baseCoin->meta['precision'] ?? 8).' '. $baseCoin->symbol];
            return redirect()->route('cart')->withNotify($notify);
        }

        try {
            DB::beginTransaction();

            $platformTrx = getTrx();
            $transactionParams = [
                'trx' => $platformTrx,
                'details' => 'Purchased ' . $cartItems->sum('quantity') . ' lottery ticket(s) from cart with ' . $baseCoin->name,
            ];
            $userBaseCoinBalanceInstance->debit($totalPriceInBaseCoin, 'lottery_cart_purchase', $transactionParams);

            foreach ($cartItems as $cartItem) {
                if(!$cartItem->lottery || $cartItem->lottery->status == Status::DISABLE || $cartItem->lottery->draw_date < now()){
                    throw new \Exception(trans('One or more lotteries in your cart are no longer available.'));
                }
                if($cartItem->lottery->num_of_available_tickets < $cartItem->quantity){
                     throw new \Exception(trans('Not enough available tickets for lottery: ') . $cartItem->lottery->name);
                }

                $pickedTicket                 = new PickedTicket();
                $pickedTicket->user_id        = $user->id;
                $pickedTicket->lottery_id     = $cartItem->lottery_id;
                $pickedTicket->choosen_tickets = $cartItem->choosen_tickets;
                $pickedTicket->quantity       = $cartItem->quantity;
                $pickedTicket->price          = $cartItem->lottery->price * $cartItem->quantity;
                $pickedTicket->trx            = $platformTrx; // Associate with the coin transaction
                $pickedTicket->save();

                // Decrease available tickets for the lottery
                $lotteryToUpdate = Lottery::find($cartItem->lottery_id);
                if($lotteryToUpdate){
                    $lotteryToUpdate->num_of_available_tickets -= $cartItem->quantity;
                    $lotteryToUpdate->save();
                }
                $cartItem->delete();
            }

            DB::commit();
            $notify[] = ['success', 'Lottery tickets purchased successfully using your coins!'];
            return redirect()->route('user.lottery.purchased')->withNotify($notify);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("LotteryCoinPurchase Error: " . $e->getMessage() . " for user " . $user->id . " TRX: " . ($platformTrx ?? 'N/A') );
            $notify[] = ['error', trans('An error occurred: ') . $e->getMessage()];
            return redirect()->route('cart')->withNotify($notify);
        }
    }

    public function finalizeLotteryPurchaseFromGateway(Request $request, $depositTrx)
    {
        $user = auth()->user();
        $deposit = Deposit::where('user_id', $user->id)
                          ->where('trx', $depositTrx)
                          ->where('status', Status::PAYMENT_SUCCESS) // Ensure deposit was successful
                          ->first();

        if (!$deposit) {
            \Log::error("FinalizeLotteryPurchase: Deposit TRX {$depositTrx} not found or not successful for user {$user->id}.");
            $notify[] = ['error', trans('Payment record not found or payment was not successful.')];
            return redirect()->route('user.deposit.history')->withNotify($notify);
        }

        // Retrieve cart details from session (set by cartItems method)
        $lotteryCartDetails = session()->get('lotteryCartDetails');

        if (empty($lotteryCartDetails)) {
            \Log::warning("FinalizeLotteryPurchase: Lottery cart details not found in session for user {$user->id}, deposit TRX {$depositTrx}.");
            // This might mean the session expired or was cleared.
            // Or the deposit was not for a lottery purchase if remark was not specific enough.
            // Check deposit remark or detail if such info was stored there as a fallback.
            $notify[] = ['info', trans('Your lottery purchase session may have expired or tickets already processed. Please check your purchased lotteries.')];
            return redirect()->route('user.lottery.purchased')->withNotify($notify);
        }

        $baseCoin = CoinType::getBaseCoin(); // For awarding bonus
        $overallPurchaseTrx = $deposit->trx; // Use the deposit's TRX for PickedTicket for consistency with gateway payments

        try {
            DB::beginTransaction();

            $totalAwardedBonus = 0;

            foreach ($lotteryCartDetails as $cartItemData) {
                $lottery = Lottery::find($cartItemData['lottery_id']);
                if (!$lottery || $lottery->status == Status::DISABLE || $lottery->draw_date < now() || $lottery->num_of_available_tickets < $cartItemData['quantity']) {
                    \Log::warning("FinalizeLotteryPurchase: Lottery ID {$cartItemData['lottery_id']} unavailable for user {$user->id}, deposit TRX {$depositTrx}. Skipping this item.");
                    // Optionally, add a specific notification to the user about this skipped item
                    continue;
                }

                // Create PickedTicket
                $pickedTicket = new PickedTicket();
                $pickedTicket->user_id = $user->id;
                $pickedTicket->lottery_id = $lottery->id;
                // choosen_tickets should be part of $cartItemData if they were pre-selected.
                // This needs to be ensured when 'lotteryCartDetails' is populated in session.
                $pickedTicket->choosen_tickets = $cartItemData['choosen_tickets'] ?? [];
                $pickedTicket->quantity = $cartItemData['quantity'];
                $pickedTicket->price = $cartItemData['price_per_ticket'] * $cartItemData['quantity']; // Use price from session for consistency
                $pickedTicket->trx = $overallPurchaseTrx;
                $pickedTicket->save();

                $lottery->num_of_available_tickets -= $cartItemData['quantity'];
                $lottery->save();

                // Award coin bonus if applicable
                if ($baseCoin && $lottery->direct_purchase_coin_reward > 0) {
                    $rewardAmount = $cartItemData['quantity'] * $lottery->direct_purchase_coin_reward;
                    if ($rewardAmount > 0) {
                        $userBaseCoinBalance = UserCoinBalance::getOrCreateBalance($user->id, $baseCoin->id);
                        $rewardTrxParams = [
                            'trx' => getTrx(), // A new, unique TRX for the coin bonus transaction itself
                            'details' => trans('Bonus for purchasing :qty ticket(s) for :lottery_name (Order: :order_trx)', [
                                'qty' => $cartItemData['quantity'],
                                'lottery_name' => __($lottery->name),
                                'order_trx' => $overallPurchaseTrx
                                ]),
                            'related_transactionable_id' => $pickedTicket->id, // Link to the picked ticket record
                            'related_transactionable_type' => get_class($pickedTicket)
                        ];
                        $userBaseCoinBalance->credit($rewardAmount, 'lottery_purchase_reward', $rewardTrxParams);
                        $totalAwardedBonus += $rewardAmount;
                    }
                }
            }

            DB::commit();
            session()->forget('lotteryCartDetails');
            session()->forget('cartItemPriceTotal');

            $successMessage = trans('Lottery tickets purchased successfully!');
            if($totalAwardedBonus > 0 && $baseCoin){
                $successMessage .= ' ' . trans('You have been awarded :amount :coin_symbol as a bonus.', ['amount' => showAmount($totalAwardedBonus, $baseCoin->meta['precision'] ?? 8), 'coin_symbol' => $baseCoin->symbol]);
            }
            $notify[] = ['success', $successMessage];
            return redirect()->route('user.lottery.purchased')->withNotify($notify);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("FinalizeLotteryPurchase Error: " . $e->getMessage() . " for user " . $user->id . ", deposit TRX " . $depositTrx);
            $notify[] = ['error', trans('An error occurred while finalizing your lottery ticket purchase: ') . $e->getMessage()];
            return redirect()->route('cart')->withNotify($notify);
        }
    }
}
