<?php

namespace App\Http\Controllers\Admin\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use Illuminate\Http\Request;

class ZarinpalController extends Controller
{
    public function edit()
    {
        $gateway = Gateway::where('code', 'zarinpal')->first();
        if (!$gateway) {
            $gateway = new Gateway();
            $gateway->code = 'zarinpal';
            $gateway->name = 'Zarinpal';
            $gateway->alias = 'zarinpal';
            $gateway->description = 'Zarinpal Payment Gateway';
            $gateway->save();
        }
        $pageTitle = 'Zarinpal Gateway';
        return view('admin.gateways.zarinpal.edit', compact('pageTitle', 'gateway'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'merchant_id' => 'required|string',
            'sandbox_mode' => 'nullable|in:on',
        ]);

        $gateway = Gateway::where('code', 'zarinpal')->first();
        $gateway->extra = json_encode([
            'merchant_id' => $request->merchant_id,
            'sandbox_mode' => $request->sandbox_mode ? true : false,
            'currency_note' => 'پرداخت به ریال انجام خواهد شد',
        ]);
        $gateway->save();

        $notify[] = ['success', 'Zarinpal gateway updated successfully'];
        return back()->withNotify($notify);
    }
}
