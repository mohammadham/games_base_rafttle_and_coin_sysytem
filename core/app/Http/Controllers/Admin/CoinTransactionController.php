<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CoinTransaction;
use App\Models\User;
use App\Models\CoinType;

class CoinTransactionController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = trans('Coin Transaction History');

        $query = CoinTransaction::with(['user', 'coinType', 'apiKey.user', 'createdByAdmin'])
                                ->orderBy('created_at', 'desc');

        // Filtering
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('trx', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('username', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('coinType', function ($coinQuery) use ($search) {
                      $coinQuery->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('user_id') && is_numeric($request->user_id)) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('coin_type_id') && is_numeric($request->coin_type_id)) {
            $query->where('coin_type_id', $request->coin_type_id);
        }

        if ($request->filled('trx_type') && in_array($request->trx_type, ['+', '-'])) {
            $query->where('trx_type', $request->trx_type);
        }

        if ($request->filled('remark')) {
            $query->where('remark', 'like', "%{$request->remark}%");
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->paginate(getPaginate());
        $users = User::orderBy('username')->select('id', 'username')->get(); // For filter dropdown
        $coinTypes = CoinType::orderBy('name')->select('id', 'name', 'code')->get(); // For filter dropdown

        // For search form to retain filter values
        $selectedFilters = $request->only(['search', 'user_id', 'coin_type_id', 'trx_type', 'remark', 'start_date', 'end_date']);


        return view('admin.coin_transaction.index', compact('pageTitle', 'transactions', 'users', 'coinTypes', 'selectedFilters'));
        // View admin.coin_transaction.index needs to be created
    }

    // Optional: A method to view details of a specific transaction if needed
    // public function detail($id)
    // {
    //     $pageTitle = trans('Coin Transaction Detail');
    //     $transaction = CoinTransaction::with(['user', 'coinType', 'apiKey.user', 'createdByAdmin', 'relatedTransactionable'])->findOrFail($id);
    //     return view('admin.coin_transaction.detail', compact('pageTitle', 'transaction'));
    //     // View admin.coin_transaction.detail needs to be created
    // }
}
