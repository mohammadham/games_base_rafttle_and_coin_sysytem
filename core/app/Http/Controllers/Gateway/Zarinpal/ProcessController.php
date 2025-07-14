<?php

namespace App\Http\Controllers\Gateway\Zarinpal;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use App\Lib\PaymentGateway\PaymentManager;
use App\Models\Deposit;
use Illuminate\Http\Request;

class ProcessController extends Controller
{
    public static function process(Deposit $deposit)
    {
        $gateway = $deposit->gateway;
        $manager = new PaymentManager();
        $zarinpal = $manager->getGateway('zarinpal');

        if (!$zarinpal) {
            $send['error'] = true;
            $send['message'] = 'Zarinpal gateway not found';
            return json_encode($send);
        }

        $amount = (float)$deposit->final_amount;
        $currency = $deposit->method_currency;
        $description = 'Payment for deposit ' . $deposit->trx;
        $callbackUrl = route('ipn.zarinpal');

        $paymentResult = $zarinpal->requestPayment($amount, $currency, $description, $callbackUrl, [
            'order_id' => $deposit->trx,
            'user_id' => $deposit->user_id,
        ]);

        if ($paymentResult['success']) {
            $send['redirect'] = true;
            $send['redirect_url'] = $paymentResult['redirect_url'];
        } else {
            $send['error'] = true;
            $send['message'] = $paymentResult['message'];
        }

        return json_encode($send);
    }

    public function ipn(Request $request)
    {
        $track = session()->get('Track');
        $deposit = Deposit::where('trx', $track)->orderBy('id', 'DESC')->first();

        if ($deposit->status == Status::PAYMENT_SUCCESS) {
            $notify[] = ['error', 'Invalid request'];
            return to_route(gatewayRedirectUrl())->withNotify($notify);
        }

        $manager = new PaymentManager();
        $zarinpal = $manager->getGateway('zarinpal');

        if (!$zarinpal) {
            $notify[] = ['error', 'Zarinpal gateway not found'];
            return to_route(gatewayRedirectUrl())->withNotify($notify);
        }

        $verificationResult = $zarinpal->verifyPayment($request, [
            'payment_id' => $request->Authority,
            'amount_in_rial' => (int)round($deposit->final_amount * 10)
        ]);

        if ($verificationResult['success']) {
            PaymentController::userDataUpdate($deposit);
            $notify[] = ['success', 'Payment captured successfully'];
            return to_route(gatewayRedirectUrl(true))->withNotify($notify);
        }

        $notify[] = ['error', $verificationResult['message']];
        return to_route(gatewayRedirectUrl())->withNotify($notify);
    }
}
