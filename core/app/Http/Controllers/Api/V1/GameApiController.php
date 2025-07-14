<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CoinType;
use App\Models\User;
use App\Models\UserCoinBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GameApiController extends Controller
{
    public function creditCoin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'coin_code' => 'required|string|exists:coin_types,code',
            'amount' => 'required|numeric|gt:0',
            'transaction_id' => 'required|string|unique:coin_transactions,transaction_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::find($request->user_id);
        $coinType = CoinType::where('code', $request->coin_code)->first();

        $userCoinBalance = UserCoinBalance::firstOrCreate(
            ['user_id' => $user->id, 'coin_type_id' => $coinType->id],
            ['balance' => 0]
        );

        $userCoinBalance->balance += $request->amount;
        $userCoinBalance->save();

        // Create a new coin transaction
        $user->coinTransactions()->create([
            'coin_type_id' => $coinType->id,
            'amount' => $request->amount,
            'transaction_type' => 'credit',
            'transaction_id' => $request->transaction_id,
            'details' => 'Credited by game server',
        ]);

        return response()->json(['success' => true, 'message' => 'Coin credited successfully']);
    }

    public function debitCoin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'coin_code' => 'required|string|exists:coin_types,code',
            'amount' => 'required|numeric|gt:0',
            'transaction_id' => 'required|string|unique:coin_transactions,transaction_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::find($request->user_id);
        $coinType = CoinType::where('code', $request->coin_code)->first();

        $userCoinBalance = UserCoinBalance::where('user_id', $user->id)
            ->where('coin_type_id', $coinType->id)
            ->first();

        if (!$userCoinBalance || $userCoinBalance->balance < $request->amount) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance'], 400);
        }

        $userCoinBalance->balance -= $request->amount;
        $userCoinBalance->save();

        // Create a new coin transaction
        $user->coinTransactions()->create([
            'coin_type_id' => $coinType->id,
            'amount' => $request->amount,
            'transaction_type' => 'debit',
            'transaction_id' => $request->transaction_id,
            'details' => 'Debited by game server',
        ]);

        return response()->json(['success' => true, 'message' => 'Coin debited successfully']);
    }

    public function testConnection(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Connection successful']);
    }
}
