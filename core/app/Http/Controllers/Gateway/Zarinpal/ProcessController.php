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
        $params = json_decode($gateway->gateway_parameters);

        // توجه: ساختار gateway_parameters به صورت آبجکت‌های {title, global, value} است
        // مثال: $params->merchant_id->value

        // تنظیمات درگاه
        $mode = strtolower($params->mode->value ?? 'sandbox');
        $merchantId = trim($params->merchant_id->value ?? '');

        // در حالت Sandbox از Merchant ID تصادفی استفاده کن
        if ($mode === 'sandbox' && empty($merchantId)) {
            $merchantId = self::generateUUID();
            if(empty($merchantId)){
            // این یک merchant ID تستی معتبر برای sandbox است
            $merchantId = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';
            }
        }

        // ارز مقصد (IRT/IRR) - اولویت: انتخاب کاربر (deposit.method_currency) سپس تنظیم ادمین
        $targetCurrency = strtoupper($deposit->method_currency ?: ($params->preferred_currency->value ?? 'IRT'));
        if (!in_array($targetCurrency, ['IRT', 'IRR'])) {
            $targetCurrency = 'IRT';
        }

        // اگر قبلاً نرخ ارز و مبلغ نهایی ذخیره شده، همان را استفاده کن تا از تغییر نرخ جلوگیری شود
        $hasFrozenRate = !empty($deposit->exchange_rate_used) && !empty($deposit->final_amount) && in_array(strtoupper($deposit->method_currency), ['IRT', 'IRR']);

        if (!$hasFrozenRate) {
            // دریافت نرخ ارز
            $exchangeApiUrl = $params->exchange_api_url->value ?? null;
            $exchangeJsonPath = $params->exchange_json_path->value ?? null;
            $fallbackRate = (float)($params->fallback_rate->value ?? 60000);
            $cacheMinutes = (int)($params->cache_minutes->value ?? 30);

            // اگر API نرخ را به ریال می‌دهد (IRR)، تبدیل به تومان (IRT) انجام شود
            $apiRateUnit = strtoupper($params->api_rate_unit->value ?? 'IRT');

            $exchangeData = ExchangeRateService::getRate(
                $exchangeApiUrl,
                $exchangeJsonPath,
                $fallbackRate,
                $cacheMinutes
            );

            $rateIrtPerUsd = (float) $exchangeData['rate'];

            // فقط برای نرخ‌های داینامیک (API/Cache) واحد را نرمال‌سازی می‌کنیم.
            // چون fallback_rate طبق عنوان «USD to Toman» در نظر گرفته شده است.
            if (in_array($exchangeData['source'], ['api', 'cache']) && $apiRateUnit === 'IRR') {
                $rateIrtPerUsd = $rateIrtPerUsd / 10;
            }

            // مبلغ قابل پرداخت به ارز پایه (USD)
            $payableUsd = (float) ($deposit->amount + $deposit->charge);

            // مبلغ قابل پرداخت به ارز مقصد
            $amountInIran = ExchangeRateService::convertToIranianCurrency(
                $payableUsd,
                $rateIrtPerUsd,
                $targetCurrency
            );

            // نرخ نمایشی مطابق ارز مقصد
            $displayRate = $rateIrtPerUsd * ($targetCurrency === 'IRR' ? 10 : 1);

            // ذخیره اطلاعات نرخ ارز در deposit
            $deposit->method_currency = $targetCurrency;
            $deposit->original_amount = $payableUsd;
            $deposit->exchange_rate_used = $displayRate;
            $deposit->rate = $displayRate;
            $deposit->final_amount = $amountInIran;
            $deposit->from_api = in_array($exchangeData['source'], ['api', 'cache']) ? 1 : 0;
            $deposit->save();
        }

        $amountInIran = (int) $deposit->final_amount;

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
                'amount' => (int) $amountInIran,
                'currency' => $targetCurrency,
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
                'deposit_trx' => $deposit->trx,
                'status' => $status,
                'authority' => $authority,
            ]);

            // ثبت وضعیت لغو/ناموفق
            $deposit->status = Status::PAYMENT_REJECT;
            $deposit->gateway_response = json_encode([
                'callback' => $request->all(),
                'message' => 'User cancelled or payment status not OK',
            ]);
            $deposit->save();

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

        $mode = strtolower($params->mode->value ?? 'sandbox');
        $merchantId = trim($params->merchant_id->value ?? '');

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
                $deposit->authority = $authority ?: $deposit->authority;
                $deposit->save();

                // به‌روزرسانی وضعیت کاربر و تراکنش
                PaymentController::userDataUpdate($deposit);

                return redirect()->route('user.payment.result', [
                    'trx' => $deposit->trx,
                    'status' => 'success'
                ]);
            } else {
                // پرداخت ناموفق
                $deposit->status = Status::PAYMENT_REJECT;
                $deposit->authority = $authority ?: $deposit->authority;
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

            $deposit->status = Status::PAYMENT_REJECT;
            $deposit->gateway_response = json_encode([
                'error' => $e->getMessage(),
                'callback' => $request->all(),
            ]);
            $deposit->save();

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
