<?php

namespace App\Http\Controllers\Gateway\Zarinpal;

use App\Constants\Status;
use App\Models\Deposit;
use App\Http\Controllers\Gateway\PaymentController;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessController extends Controller
{
    /**
     * Zarinpal Payment Gateway - REST API v4
     * پشتیبانی از حالت Sandbox و Production
     */

    // آدرس‌های API زرین‌پال
    private const SANDBOX_REQUEST_URL = 'https://sandbox.zarinpal.com/pg/v4/payment/request.json';
    private const SANDBOX_VERIFY_URL = 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json';
    private const SANDBOX_STARTPAY_URL = 'https://sandbox.zarinpal.com/pg/StartPay/';

    private const PRODUCTION_REQUEST_URL = 'https://api.zarinpal.com/pg/v4/payment/request.json';
    private const PRODUCTION_VERIFY_URL = 'https://api.zarinpal.com/pg/v4/payment/verify.json';
    private const PRODUCTION_STARTPAY_URL = 'https://www.zarinpal.com/pg/StartPay/';

    /**
     * پردازش درخواست پرداخت
     *
     * @param Deposit $deposit
     * @return string JSON response
     */
    public static function process($deposit)
    {
        $gateway = $deposit->gateway;
        $gatewayCurrency = $deposit->gatewayCurrency();
        $params = json_decode($gateway->gateway_parameters);

        // تنظیمات درگاه
        $mode = $params->mode ?? 'sandbox';
        $merchantId = $params->merchant_id ?? '';

        // در حالت Sandbox از Merchant ID تصادفی استفاده کن
        if ($mode === 'sandbox' && empty($merchantId)) {
            $merchantId = self::generateUUID();
            if(empty($merchantId)){
            // این یک merchant ID تستی معتبر برای sandbox است
            $merchantId = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';
            }
        }

        // اگر merchant_id هنوز خالی است و در حالت production هستیم
        if (empty($merchantId) && $mode === 'production') {
            $send['error'] = true;
            $send['message'] = 'Merchant ID تنظیم نشده است. لطفاً در تنظیمات درگاه، Merchant ID خود را وارد کنید.';
            return json_encode($send);
        }
        

        // دریافت نرخ ارز
        $exchangeApiUrl = $params->exchange_api_url ?? null;
        $exchangeJsonPath = $params->exchange_json_path ?? null;
        $fallbackRate = (float)($params->fallback_rate ?? 60000);
        $cacheMinutes = (int)($params->cache_minutes ?? 30);

        $exchangeData = ExchangeRateService::getRate(
            $exchangeApiUrl,
            $exchangeJsonPath,
            $fallbackRate,
            $cacheMinutes
        );

        $exchangeRate = $exchangeData['rate'];

        // تبدیل مبلغ به تومان
        $preferredCurrency = $params->preferred_currency ?? 'IRT';
        $amountInIRT = ExchangeRateService::convertToIranianCurrency(
            $deposit->amount + $deposit->charge,
            $exchangeRate,
            $preferredCurrency
        );

        // ذخیره اطلاعات نرخ ارز در deposit
        $deposit->original_amount = $deposit->amount;
        $deposit->exchange_rate_used = $exchangeRate;
        $deposit->final_amount = $amountInIRT;
        $deposit->save();

        // انتخاب URL های API
        $requestUrl = ($mode === 'sandbox') ? self::SANDBOX_REQUEST_URL : self::PRODUCTION_REQUEST_URL;
        $startPayUrl = ($mode === 'sandbox') ? self::SANDBOX_STARTPAY_URL : self::PRODUCTION_STARTPAY_URL;

        // Callback URL با trx برای جلوگیری از مشکل session
        $callbackUrl = route('ipn.Zarinpal', ['trx' => $deposit->trx]);

        // توضیحات تراکنش
        $description = 'پرداخت سفارش ' . $deposit->trx;

        // ارسال درخواست به زرین‌پال
        try {
            $response = Http::timeout(30)->post($requestUrl, [
                'merchant_id' => $merchantId,
                'amount' => $amountInIRT,
                'currency' => $preferredCurrency,
                'callback_url' => $callbackUrl,
                'description' => $description,
                'metadata' => [
                    'email' => $deposit->user->email ?? '',
                    'mobile' => $deposit->user->mobile ?? '',
                    'order_id' => $deposit->trx
                ]
            ]);

            $result = $response->json();

            Log::info('Zarinpal Request Response', [
                'deposit_trx' => $deposit->trx,
                'response' => $result
            ]);

            // بررسی موفقیت درخواست
            if (isset($result['data']['code']) && $result['data']['code'] == 100) {
                $authority = $result['data']['authority'];

                // ذخیره Authority در دیتابیس
                $deposit->authority = $authority;
                $deposit->gateway_response = json_encode($result);
                $deposit->save();

                $send['redirect'] = true;
                $send['redirect_url'] = $startPayUrl . $authority;
            } else {
                $errorMessage = $result['errors']['message'] ?? 'خطای نامشخص';
                $errorCode = $result['errors']['code'] ?? 'N/A';

                Log::error('Zarinpal Request Failed', [
                    'deposit_trx' => $deposit->trx,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage
                ]);

                $send['error'] = true;
                $send['message'] = 'خطای زرین‌پال: ' . $errorMessage . ' (کد: ' . $errorCode . ')';
            }
        } catch (\Exception $e) {
            Log::error('Zarinpal Connection Error', [
                'deposit_trx' => $deposit->trx,
                'error' => $e->getMessage()
            ]);

            $send['error'] = true;
            $send['message'] = 'خطا در اتصال به درگاه زرین‌پال: ' . $e->getMessage();
        }

        return json_encode($send);
    }

    /**
     * پردازش بازگشت از درگاه (IPN/Callback)
     *
     * @param Request $request
     * @param string|null $trx
     * @return \Illuminate\Http\RedirectResponse
     */
    public function ipn(Request $request, $trx = null)
    {
        $authority = $request->Authority;
        $status = $request->Status;

        // پیدا کردن Deposit
        $deposit = null;

        // اول با trx از URL تلاش کن
        if ($trx) {
            $deposit = Deposit::where('trx', $trx)->first();
        }

        // اگر پیدا نشد، با authority تلاش کن
        if (!$deposit && $authority) {
            $deposit = Deposit::where('authority', $authority)->first();
        }

        // اگر هنوز پیدا نشد، با session تلاش کن
        if (!$deposit) {
            $track = session()->get('Track');
            if ($track) {
                $deposit = Deposit::where('trx', $track)->first();
            }
        }

        // اگر deposit پیدا نشد
        if (!$deposit) {
            Log::error('Zarinpal IPN: Deposit not found', [
                'trx' => $trx,
                'authority' => $authority
            ]);
            $notify[] = ['error', 'تراکنش یافت نشد'];
            return redirect()->route('home')->withNotify($notify);
        }

        // اگر کاربر لغو کرده
        if ($status !== 'OK') {
            Log::info('Zarinpal: Payment cancelled by user', [
                'deposit_trx' => $deposit->trx
            ]);

            return redirect()->route('user.payment.result', [
                'trx' => $deposit->trx,
                'status' => 'cancelled'
            ]);
        }

        // اگر قبلاً تایید شده
        if ($deposit->status == Status::PAYMENT_SUCCESS) {
            return redirect()->route('user.payment.result', [
                'trx' => $deposit->trx,
                'status' => 'already_verified'
            ]);
        }

        // تایید پرداخت (Verify)
        $gateway = $deposit->gateway;
        $params = json_decode($gateway->gateway_parameters);

        $mode = $params->mode ?? 'sandbox';
        $merchantId = $params->merchant_id ?? '';

        if ($mode === 'sandbox' && empty($merchantId)) {
            $merchantId = self::generateUUID();
        }

        $verifyUrl = ($mode === 'sandbox') ? self::SANDBOX_VERIFY_URL : self::PRODUCTION_VERIFY_URL;

        try {
            $response = Http::timeout(30)->post($verifyUrl, [
                'merchant_id' => $merchantId,
                'amount' => (int) $deposit->final_amount,
                'authority' => $authority
            ]);

            $result = $response->json();

            Log::info('Zarinpal Verify Response', [
                'deposit_trx' => $deposit->trx,
                'response' => $result
            ]);

            // ذخیره پاسخ کامل
            $deposit->gateway_response = json_encode($result);

            // بررسی موفقیت تایید
            $code = $result['data']['code'] ?? null;

            if ($code == 100 || $code == 101) {
                // پرداخت موفق
                $deposit->ref_id = $result['data']['ref_id'] ?? null;
                $deposit->card_pan = $result['data']['card_pan'] ?? null;
                $deposit->save();

                // به‌روزرسانی وضعیت کاربر و تراکنش
                PaymentController::userDataUpdate($deposit);

                return redirect()->route('user.payment.result', [
                    'trx' => $deposit->trx,
                    'status' => 'success'
                ]);
            } else {
                // پرداخت ناموفق
                $deposit->save();

                Log::warning('Zarinpal Verify Failed', [
                    'deposit_trx' => $deposit->trx,
                    'code' => $code
                ]);

                return redirect()->route('user.payment.result', [
                    'trx' => $deposit->trx,
                    'status' => 'failed',
                    'code' => $code
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Zarinpal Verify Error', [
                'deposit_trx' => $deposit->trx,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('user.payment.result', [
                'trx' => $deposit->trx,
                'status' => 'error'
            ]);
        }
    }

    /**
     * تولید UUID تصادفی برای Sandbox
     *
     * @return string
     */
    private static function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
