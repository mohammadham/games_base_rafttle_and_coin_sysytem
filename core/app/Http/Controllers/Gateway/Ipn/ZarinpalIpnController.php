<?php

namespace App\Http\Controllers\Gateway\Ipn;

use App\Http\Controllers\Controller;
use App\Lib\PaymentGateway\Zarinpal\Processor;
use App\Models\Deposit;
use Illuminate\Http\Request;

class ZarinpalIpnController extends Controller
{
    public function ipn(Request $request, $trx)
    {
        $deposit = Deposit::where('trx', $trx)->first();

        if (!$deposit) {
            return response()->json(['error' => 'Deposit not found'], 404);
        }

        $gateway = $deposit->gateway;
        $processor = new Processor();
        $verification = $processor->verify($deposit);

        if ($verification['success']) {
            $deposit->status = 1; // Completed
            $deposit->save();

            // Add transaction record
            $transaction = new \App\Models\Transaction();
            $transaction->user_id = $deposit->user_id;
            $transaction->amount = $deposit->amount;
            $transaction->post_balance = $deposit->user->balance + $deposit->amount;
            $transaction->charge = $deposit->charge;
            $transaction->trx_type = '+';
            $transaction->details = 'Deposit via ' . $gateway->name;
            $transaction->trx = $deposit->trx;
            $transaction->save();

            // Update user balance
            $deposit->user->balance += $deposit->amount;
            $deposit->user->save();

            return redirect()->route('user.deposit.history')->with('success', 'Payment successful');
        }

        return redirect()->route('user.deposit.history')->with('error', $verification['message']);
    }
}
