<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Lib\PaymentGateway\PaymentManager;
use App\Models\Deposit;
use App\Models\User;
use App\Models\GatewayCurrency;
use App\Models\Transaction;
use App\Constants\Status;
use Illuminate\Support\Facades\Log; // Added for logging

class PaymentController extends Controller
{
    public function __construct()
    {
        // Middleware for authentication can be applied here or in routes
        // $this->middleware('auth'); // Assuming user must be logged in
    }

    public function initiatePayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01', // Min amount should be sensible
            'gateway_code' => 'required|string',
            // 'currency' => 'sometimes|required|string', // Optional: if user can select currency
        ]);

        $user = auth()->user();
        if (!$user) {
            $notify[] = ['error', 'برای ادامه باید ابتدا وارد شوید.'];
            return redirect()->route('user.login')->withNotify($notify); // Adjust route as needed
        }

        $amount = (float) $request->amount;
        $gatewayCode = $request->gateway_code;
        // Determine currency: from request or default from general settings
        $currency = strtoupper($request->input('currency', gs('cur_text')));

        $gateway = PaymentManager::gateway($gatewayCode);

        if (!$gateway) {
            $notify[] = ['error', 'درگاه پرداخت انتخاب شده نامعتبر یا غیرفعال است.'];
            return back()->withNotify($notify)->withInput();
        }

        $gatewayConfig = $gateway->getConfig();
        if(isset($gatewayConfig['success']) && $gatewayConfig['success'] === false){
            $notify[] = ['error', $gatewayConfig['message'] ?? 'خطا در بارگذاری تنظیمات درگاه.'];
            return back()->withNotify($notify)->withInput();
        }

        $activeGatewayCurrency = GatewayCurrency::where('method_code', $gatewayModel->code ?? $gatewayCode) // Use code from GatewayModel if available
                                ->where('currency', $currency)->first();

        if (!$activeGatewayCurrency) {
            $notify[] = ['error', "ارز {$currency} برای این درگاه ({$gateway->getName()}) پشتیبانی نمی‌شود."];
            return back()->withNotify($notify)->withInput();
        }

        if ($amount < $activeGatewayCurrency->min_amount || $amount > $activeGatewayCurrency->max_amount) {
            $notify[] = ['error', "مبلغ تراکنش باید بین {$activeGatewayCurrency->min_amount} و {$activeGatewayCurrency->max_amount} {$currency} باشد."];
            return back()->withNotify($notify)->withInput();
        }

        $charge = $activeGatewayCurrency->fixed_charge + ($amount * $activeGatewayCurrency->percent_charge / 100);
        $payableAmountInSiteCurrency = $amount + $charge; // Total amount user will pay in site's currency for the value of $amount

        // Amount to be sent to the gateway, in gateway's expected currency
        $finalAmountForGateway = $payableAmountInSiteCurrency;
        $gatewayExpectedCurrency = $currency; // Default to site currency

        if (strtoupper($gateway->getName()) === 'ZARINPAL') {
            if (strtoupper($currency) === 'IRT') {
                $finalAmountForGateway = $payableAmountInSiteCurrency * 10; // Convert Toman to Rial
                $gatewayExpectedCurrency = 'IRR';
            } elseif (strtoupper($currency) === 'IRR') {
                $finalAmountForGateway = $payableAmountInSiteCurrency;
                $gatewayExpectedCurrency = 'IRR';
            } else {
                 $notify[] = ['error', 'واحد پول برای زرین پال باید تومان یا ریال باشد.'];
                 return back()->withNotify($notify)->withInput();
            }
        }
        // Add similar blocks for other gateways if they have specific currency requirements

        $deposit = new Deposit();
        $deposit->user_id = $user->id;
        $deposit->method_code = $activeGatewayCurrency->method_code;
        $deposit->method_currency = strtoupper($currency);
        $deposit->amount = $amount; // The actual value user wants to deposit/buy
        $deposit->charge = $charge;
        $deposit->rate = $activeGatewayCurrency->rate;
        $deposit->final_amo = $finalAmountForGateway; // Amount in gateway's currency (e.g. Rials for Zarinpal)
        $deposit->btc_amo = 0;
        $deposit->btc_wallet = ''; // Will store Zarinpal Authority
        $deposit->trx = getTrx();
        $deposit->status = Status::PAYMENT_INITIATE;
        $deposit->detail = ['initial_amount_site_currency' => $amount, 'charge_site_currency' => $charge, 'payable_site_currency' => $payableAmountInSiteCurrency];
        $deposit->save();

        session()->put('Track', $deposit->trx);

        // Define callback URL more robustly
        $callbackUrl = route('user.deposit.callback', ['gatewayName' => $gatewayCode, 'trx' => $deposit->trx]);

        $paymentMetadata = [
            'user_id' => $user->id,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'order_id' => $deposit->trx, // Using system's TRX as order_id for gateway
        ];

        Log::info("Initiating payment for TRX: {$deposit->trx} via {$gateway->getName()} for amount: {$payableAmountInSiteCurrency} {$currency} (Gateway: {$finalAmountForGateway} {$gatewayExpectedCurrency})");

        $gatewayResponse = $gateway->requestPayment($payableAmountInSiteCurrency, $currency, "پرداخت سفارش شماره {$deposit->trx}", $callbackUrl, $paymentMetadata);

        $depositDetails = (array) $deposit->detail; // Ensure it's an array
        $depositDetails['gateway_request_payload'] = $paymentMetadata; // For debugging
        $depositDetails['gateway_request_response'] = $gatewayResponse; // For debugging

        if (isset($gatewayResponse['success']) && $gatewayResponse['success']) {
            $deposit->btc_wallet = $gatewayResponse['payment_id'] ?? null; // Zarinpal Authority
            if(isset($gatewayResponse['amount_in_rial'])) { // Specific to Zarinpal or similar gateways
                $depositDetails['amount_in_gateway_currency_for_verify'] = $gatewayResponse['amount_in_rial'];
            } else {
                // Fallback if not provided by gateway, though Zarinpal class adds this.
                // This is crucial for verification.
                $depositDetails['amount_in_gateway_currency_for_verify'] = $finalAmountForGateway;
            }
            $deposit->detail = $depositDetails;
            $deposit->save();

            if (isset($gatewayResponse['redirect_url'])) {
                return redirect($gatewayResponse['redirect_url']);
            } else {
                Log::error("Payment initiation for TRX: {$deposit->trx} - Redirect URL not received from gateway.");
                $notify[] = ['error', 'خطا: URL هدایت به درگاه دریافت نشد.'];
                return back()->withNotify($notify)->withInput();
            }
        } else {
            $deposit->status = Status::PAYMENT_REJECT;
            $depositDetails['initiation_error'] = $gatewayResponse['message'] ?? 'خطای نامشخص در ارتباط با درگاه.';
            $deposit->detail = $depositDetails;
            $deposit->save();
            Log::error("Payment initiation failed for TRX: {$deposit->trx} - Gateway Message: " . ($gatewayResponse['message'] ?? 'Unknown error'));
            $notify[] = ['error', $gatewayResponse['message'] ?? 'خطا در ایجاد درخواست پرداخت.'];
            return back()->withNotify($notify)->withInput();
        }
    }

    public function paymentCallback(Request $request, $gatewayCode, $trxFromRoute = null) {
        // In some cases, TRX might be in query string or POST data instead of route param
        $trx = $trxFromRoute ?? $request->input('trx', session()->get('Track'));

        if(!$trx){
            Log::error("Callback received without TRX.", ['gateway' => $gatewayCode, 'request_all' => $request->all()]);
            $notify[] = ['error', 'شناسه تراکنش نامعتبر است.'];
            return redirect()->route(auth()->check() ? strtolower(auth()->user()->access_route) . '.deposit.history' : 'home')->withNotify($notify);
        }

        $deposit = Deposit::where('trx', $trx)
                          ->where('status', Status::PAYMENT_INITIATE)
                          ->first();

        if (!$deposit) {
            Log::warning("Callback for TRX: {$trx} - Deposit not found or not in INITIATE state.", ['gateway' => $gatewayCode]);
            $notify[] = ['error', 'تراکنش یافت نشد یا قبلاً پردازش شده است.'];
            return redirect()->route(auth()->check() ? strtolower(auth()->user()->access_route) . '.deposit.history' : 'home')->withNotify($notify);
        }

        $gateway = PaymentManager::gateway($gatewayCode);
        if (!$gateway) {
            Log::error("Callback for TRX: {$trx} - Gateway '{$gatewayCode}' not found or invalid.");
            $deposit->status = Status::PAYMENT_REJECT;
            $depositDetails = (array) $deposit->detail;
            $depositDetails['callback_error'] = 'Gateway not found or invalid during callback.';
            $deposit->detail = $depositDetails;
            $deposit->save();
            $notify[] = ['error', 'درگاه پرداخت نامعتبر است.'];
            return redirect()->route(strtolower($deposit->user->access_route) . '.deposit.history')->withNotify($notify);
        }

        $depositDetails = (array) $deposit->detail;
        $amountForVerify = $depositDetails['amount_in_gateway_currency_for_verify'] ?? $deposit->final_amo;

        $storedTransactionData = [
            'payment_id' => $deposit->btc_wallet, // Authority
            'amount_in_rial' => $amountForVerify, // Amount in gateway's currency (e.g. Rials)
        ];

        Log::info("Verifying payment for TRX: {$deposit->trx} via {$gateway->getName()}", ['callback_data' => $request->all(), 'stored_for_verify' => $storedTransactionData]);

        $verificationResponse = $gateway->verifyPayment($request, $storedTransactionData);

        $depositDetails['gateway_verify_response'] = $verificationResponse;

        if (isset($verificationResponse['success']) && $verificationResponse['success']) {
            $deposit->status = Status::PAYMENT_SUCCESS;
            $deposit->admin_feedback = $verificationResponse['message'] ?? 'پرداخت موفق';

            if(isset($verificationResponse['transaction_id'])){
                 $depositDetails['gateway_transaction_id'] = $verificationResponse['transaction_id'];
            }
            if(isset($verificationResponse['card_pan'])){
                 $depositDetails['card_pan'] = $verificationResponse['card_pan'];
            }

            $deposit->detail = $depositDetails;
            $deposit->save();

            $user = $deposit->user; // User model should be eager loaded or fetched
            if(!$user){ User::find($deposit->user_id); }

            $user->balance += $deposit->amount;
            $user->save();

            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->amount = $deposit->amount;
            $transaction->post_balance = $user->balance;
            $transaction->charge = $deposit->charge;
            $transaction->trx_type = '+';
            $transaction->details = 'واریز با موفقیت از طریق ' . $gateway->getName() . ' (شناسه درگاه: ' . ($verificationResponse['transaction_id'] ?? $deposit->trx) . ')';
            $transaction->trx = $deposit->trx;
            $transaction->remark = 'deposit';
            $transaction->save();

            // Notify User (adjust as per your notification system)
            // Example:
            // notify($user, 'DEPOSIT_COMPLETE', [
            //     'method_name' => $gateway->getName(),
            //     'amount' => showAmount($deposit->amount, $deposit->method_currency),
            //     'charge' => showAmount($deposit->charge, $deposit->method_currency),
            //     'trx' => $deposit->trx,
            //     'post_balance' => showAmount($user->balance, $deposit->method_currency)
            // ]);

            Log::info("Payment successful for TRX: {$deposit->trx}. User balance updated.");
            $notify[] = ['success', 'پرداخت شما با موفقیت انجام و مبلغ به حساب شما اضافه شد.'];
            return redirect()->route(strtolower($user->access_route) . '.deposit.history')->withNotify($notify);

        } else {
            $deposit->status = Status::PAYMENT_REJECT;
            $deposit->admin_feedback = $verificationResponse['message'] ?? 'تایید پرداخت ناموفق بود.';
            $depositDetails['callback_error_message'] = $verificationResponse['message'] ?? 'Verification failed.';
            $deposit->detail = $depositDetails;
            $deposit->save();
            Log::error("Payment verification failed for TRX: {$deposit->trx} - Message: " . ($verificationResponse['message'] ?? 'Unknown error'), ['response' => $verificationResponse]);
            $notify[] = ['error', $verificationResponse['message'] ?? 'خطا در تایید پرداخت.'];
            return redirect()->route(strtolower($deposit->user->access_route) . '.deposit.history')->withNotify($notify);
        }
    }
}
