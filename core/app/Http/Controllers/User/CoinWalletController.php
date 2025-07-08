<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserCoinBalance;
use App\Models\CoinTransaction;
use App\Models\CoinType; // Added for type hinting and static calls
use App\Constants\Status; // Added for status checks
use Illuminate\Http\Request;
use App\Lib\PaymentGateway\PaymentManager; // Added for fetching gateways
use Illuminate\Support\Facades\Auth; // Using Auth facade

class CoinWalletController extends Controller
{
    public function balances()
    {
        $pageTitle = "My Coin Balances";
        $user = Auth::user();

        $coinBalances = UserCoinBalance::where('user_id', $user->id)
                                      ->whereHas('coinType', function($query){
                                          $query->where('status', Status::ENABLE); // Ensure coin type is active
                                      })
                                      ->with(['coinType' => function($query){
                                          $query->where('status', Status::ENABLE); // Double check, or rely on whereHas
                                      }])
                                      ->orderByDesc('balance') // Show higher balances first
                                      ->paginate(getPaginate());

        $baseCoin = CoinType::getBaseCoin(); // Static method from CoinType model
        $totalValueInBaseCoin = 0;

        if($baseCoin){
            // Fetch all balances for calculation, not just paginated ones, if total is desired across all coins.
            $allUserBalances = UserCoinBalance::where('user_id', $user->id)->with('coinType')->get();
            foreach($allUserBalances as $balanceItem){
                // Ensure coinType is loaded and active before conversion
                if($balanceItem->coinType && $balanceItem->coinType->status == Status::ENABLE){
                     $totalValueInBaseCoin += $balanceItem->coinType->convertToBaseCoin((float)$balanceItem->balance);
                }
            }
        }

        return view(activeTemplate() . 'user.coin_wallet.balances', compact('pageTitle', 'coinBalances', 'baseCoin', 'totalValueInBaseCoin'));
    }

    public function history(Request $request)
    {
        $pageTitle = "My Coin Transaction History";
        $user = Auth::user();

        $query = CoinTransaction::where('user_id', $user->id)
                                ->with('coinType')
                                ->orderBy('id', 'desc'); // Order by ID desc for latest first

        if ($request->filled('search')) { // General search term
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm){
                $q->where('trx', 'like', "%{$searchTerm}%")
                  ->orWhere('details', 'like', "%{$searchTerm}%")
                  ->orWhere('remark', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('coin_code')) {
            $query->whereHas('coinType', function($q) use ($request){
                $q->where('code', $request->coin_code);
            });
        }

        if ($request->filled('trx_type') && in_array($request->trx_type, ['+', '-'])) {
            $query->where('trx_type', $request->trx_type);
        }

        // Date filtering example
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $coinTransactions = $query->paginate(getPaginate())->appends($request->all()); // Append query strings to pagination links

        // For filter dropdowns
        $coinTypes = CoinType::active()->orderBy('name')->get(['name', 'code']);
        $remarks = CoinTransaction::where('user_id', $user->id)->distinct()->pluck('remark')->filter()->sort();


        return view(activeTemplate() . 'user.coin_wallet.history', compact('pageTitle', 'coinTransactions', 'coinTypes', 'remarks'));
    }

    public function showPurchaseForm()
    {
        $pageTitle = trans('Buy Coins / Add Credit');
        $gateways = PaymentManager::getAvailableGateways(); // Fetch active payment gateways
        $baseCoin = CoinType::getBaseCoin();

        if (!$baseCoin) {
            $notify[] = ['error', trans('The base coin is not configured in the system. Please contact support.')];
            return redirect()->route('user.home')->withNotify($notify);
        }

        // Assuming purchase is always for the base coin or general site credit convertible to base coin.
        return view(activeTemplate() . 'user.coin_wallet.purchase_form', compact('pageTitle', 'gateways', 'baseCoin'));
    }
}
