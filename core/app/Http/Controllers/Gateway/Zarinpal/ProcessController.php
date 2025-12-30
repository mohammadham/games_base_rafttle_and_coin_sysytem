<?php

namespace App\Http\Controllers\Gateway\Zarinpal;

use App\Constants\Status;
use App\Models\Deposit;
use App\Http\Controllers\Gateway\PaymentController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class ProcessController extends Controller
{
    /*
     * Zarinpal Gateway
     */

    public static function process($deposit)
    {
        $alias = $deposit->gateway->alias;
        $params = json_decode($deposit->gatewayCurrency()->gateway_parameter);

        // Check Mode (Sandbox or Production)
        $mode = $params->mode ?? 'sandbox';
        $merchantId = $params->merchant_id;
        $rate = $params->rate ?? 1; // Conversion rate from Base Currency to Toman

        // Convert Amount to Toman (Zarinpal accepts Toman)
        // Assuming Base Currency is USD. Formula: Amount * Rate
        $amountInToman = round($deposit->final_amount * $rate);
        
        // Prepare Zarinpal API URL
        $baseUrl = ($mode == 'sandbox') ? 'https://sandbox.zarinpal.com/pg/v4/payment' : 'https://payment.zarinpal.com/pg/v4/payment';
        
        $callbackUrl = route('ipn.'.$alias);
        $description = "Payment for Order " . $deposit->trx;

        $response = Http::post("$baseUrl/request.json", [
            'merchant_id' => $merchantId,
            'amount' => $amountInToman, // Toman
            'currency' => 'IRT',
            'callback_url' => $callbackUrl,
            'description' => $description,
            'metadata' => [
                'email' => auth()->user()->email ?? '',
                'mobile' => auth()->user()->mobile ?? '',
            ]
        ]);

        $result = $response->json();

        if (isset($result['data']['code']) && $result['data']['code'] == 100) {
            $authority = $result['data']['authority'];
            $startPayUrl = ($mode == 'sandbox') ? "https://sandbox.zarinpal.com/pg/StartPay/$authority" : "https://payment.zarinpal.com/pg/StartPay/$authority";

            $send['redirect'] = true;
            $send['redirect_url'] = $startPayUrl;
            $send['view'] = null;
        } else {
            $send['error'] = true;
            $send['message'] = 'Zarinpal Error: ' . ($result['errors']['message'] ?? 'Unknown Error');
        }

        return json_encode($send);
    }

    public function ipn(Request $request)
    {
        $authority = $request->Authority;
        $status = $request->Status;

        $track = session()->get('Track');
        $deposit = Deposit::where('trx', $track)->orderBy('id', 'DESC')->first();

        if ($status != 'OK') {
            $notify[] = ['error', 'Transaction canceled by user'];
            return redirect($deposit->failed_url)->withNotify($notify);
        }

        if (!$deposit) {
            $notify[] = ['error', 'Invalid request'];
            return redirect(route('home'))->withNotify($notify);
        }

        if ($deposit->status == Status::PAYMENT_SUCCESS) {
            $notify[] = ['success', 'Transaction already verified'];
            return redirect($deposit->success_url)->withNotify($notify);
        }

        $params = json_decode($deposit->gatewayCurrency()->gateway_parameter);
        $mode = $params->mode ?? 'sandbox';
        $merchantId = $params->merchant_id;
        $rate = $params->rate ?? 1;

        $amountInToman = round($deposit->final_amount * $rate);
        
        $baseUrl = ($mode == 'sandbox') ? 'https://sandbox.zarinpal.com/pg/v4/payment' : 'https://payment.zarinpal.com/pg/v4/payment';

        $response = Http::post("$baseUrl/verify.json", [
            'merchant_id' => $merchantId,
            'amount' => $amountInToman,
            'authority' => $authority,
        ]);

        $result = $response->json();

        if (isset($result['data']['code']) && ($result['data']['code'] == 100 || $result['data']['code'] == 101)) {
            PaymentController::userDataUpdate($deposit);

            $notify[] = ['success', 'Payment verified successfully. Ref ID: ' . $result['data']['ref_id']];
            return redirect($deposit->success_url)->withNotify($notify);
        } else {
            $notify[] = ['error', 'Transaction verification failed. Code: ' . ($result['data']['code'] ?? 'Unknown')];
            return redirect($deposit->failed_url)->withNotify($notify);
        }
    }
}
