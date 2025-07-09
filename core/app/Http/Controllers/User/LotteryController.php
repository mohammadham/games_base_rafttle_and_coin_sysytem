<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Lottery;
use App\Models\PickedTicket;
use App\Models\Winner;
use App\Models\CoinType;
use App\Models\UserCoinBalance;
use App\Constants\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LotteryController extends Controller {
    public function cartItems() {
        $cartItems = Cart::where('user_id', auth()->id())->get();

        $cartItemPrice = 0;

        foreach ($cartItems as $cartItem) {
            $cartItemPrice += $cartItem->lottery->price * $cartItem->quantity;
        }

        session()->forget('cartItemPriceTotal');
        session()->put('cartItemPriceTotal', $cartItemPrice);

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
}
