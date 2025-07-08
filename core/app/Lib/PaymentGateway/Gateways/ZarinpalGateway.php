<?php

namespace App\Lib\PaymentGateway\Gateways;

use App\Lib\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // For making HTTP requests
use App\Models\Gateway as GatewayModel; // Renamed to avoid conflict with class name if any
use App\Constants\Status; // Assuming Status::ENABLE and Status::DISABLE exist

class ZarinpalGateway implements PaymentGatewayInterface
{
    protected array $config;
    protected string $merchantId;
    protected string $paymentRequestUrl;
    protected string $paymentVerificationUrl;
    protected string $gatewayUrl;
    protected bool $isSandbox;
    protected $gatewayInfo; // To store the gateway model instance

    public function __construct(GatewayModel $gatewayModelInstance = null)
    {
        $this->gatewayInfo = $gatewayModelInstance;
        if (!$this->gatewayInfo) {
            // This case should ideally be prevented by PaymentManager returning null
            // if the gateway model itself wasn't found or provided.
             $this->config = [];
             $this->merchantId = '';
             $this->isSandbox = true; // Default to sandbox to be safe
             $this->setUrls();
             logger()->error('Zarinpal gateway model was not provided to constructor or is invalid.');
        }
        // Config is loaded on demand via getConfig or when methods like requestPayment are called and need it.
    }

    protected function setUrls(): void
    {
        if ($this->isSandbox) {
            $this->paymentRequestUrl = 'https://sandbox.zarinpal.com/pg/v4/payment/request.json';
            $this->paymentVerificationUrl = 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json';
            $this->gatewayUrl = 'https://sandbox.zarinpal.com/pg/StartPay/';
        } else {
            $this->paymentRequestUrl = 'https://api.zarinpal.com/pg/v4/payment/request.json';
            $this->paymentVerificationUrl = 'https://api.zarinpal.com/pg/v4/payment/verify.json';
            $this->gatewayUrl = 'https://www.zarinpal.com/pg/StartPay/';
        }
    }

    protected function loadConfig(): void
    {
        if (!$this->gatewayInfo || !isset($this->gatewayInfo->extra)) { // Check if gatewayInfo and extra are set
            $this->merchantId = '';
            $this->isSandbox = true;
            $this->config = []; // Ensure config is an array even if loading fails
            $this->setUrls();
            logger()->warning('ZarinpalGateway: loadConfig called with invalid or missing gatewayInfo or extra data.');
            return;
        }

        $extra = $this->gatewayInfo->extra; // extra is an object

        $this->merchantId = $extra->merchant_id ?? '';
        $this->isSandbox = (bool) ($extra->sandbox_mode ?? true); // Default to sandbox if not set

        $this->setUrls();

        $this->config = [
            'name' => $this->getName(),
            'alias' => $this->gatewayInfo->alias,
            'merchant_id' => $this->merchantId,
            'sandbox_mode' => $this->isSandbox,
            'payment_request_url' => $this->paymentRequestUrl,
            'payment_verification_url' => $this->paymentVerificationUrl,
            'gateway_url' => $this->gatewayUrl,
            'image' => $this->gatewayInfo->image ? getImage(getFilePath('gateway') . '/' . $this->gatewayInfo->image, getFileSize('gateway')) : '',
            'supported_currencies' => $this->gatewayInfo->supported_currencies, // From Gateway model
            'currency_note' => $extra->currency_note ?? 'پرداخت به ریال انجام خواهد شد',
        ];
    }

    public function getName(): string
    {
        return $this->gatewayInfo->name ?? 'ZarinPal'; // Use name from DB if available
    }

    public function getConfig(): array
    {
        if (!$this->gatewayInfo) {
             // This indicates an issue, as PaymentManager should not have provided an instance.
             return ['success' => false, 'message' => 'پیکربندی درگاه زرین‌پال بارگذاری نشده است.'];
        }
        if (empty($this->config) || ($this->gatewayInfo && ($this->config['merchant_id'] ?? null) === null && $this->merchantId === '')) { // Check if config is truly loaded
            $this->loadConfig();
        }
        return $this->config;
    }

    public function requestPayment(float $amount, string $currency, string $description, string $callbackUrl, array $additionalData = []): array
    {
        // Ensure config is loaded before proceeding
        if (empty($this->config) || ($this->gatewayInfo && ($this->config['merchant_id'] ?? null) === null && $this->merchantId === '')) {
            $this->loadConfig();
             // After loading, if still no merchantId and gatewayInfo was valid, then it's a config issue.
            if (empty($this->merchantId) && $this->gatewayInfo) {
                 logger()->error("ZarinpalGateway: Merchant ID is missing after attempting to load config for an existing gateway model.");
                 return ['success' => false, 'message' => 'مرچنت کد زرین‌پال در پیکربندی درگاه یافت نشد.'];
            }
        }

        if (!$this->gatewayInfo || $this->gatewayInfo->status == Status::DISABLE) {
            return ['success' => false, 'message' => 'درگاه زرین‌پال فعال نیست یا پیکربندی نشده است.'];
        }
        if (empty($this->merchantId)) {
            return ['success' => false, 'message' => 'مرچنت کد زرین‌پال پیکربندی نشده است.'];
        }

        // Currency conversion and validation (IRT to IRR)
        $amountInRial = $amount;
        if (strtoupper($currency) === 'IRT') {
            $amountInRial = $amount * 10;
        } elseif (strtoupper($currency) !== 'IRR') {
            return ['success' => false, 'message' => 'واحد پول نامعتبر برای زرین‌پال. فقط IRR یا IRT پشتیبانی می‌شود.'];
        }
        $amountInRial = (int)round($amountInRial);

        if ($amountInRial < 1000) { // Zarinpal minimum is 100 Toman (1000 Rials)
             return ['success' => false, 'message' => 'مبلغ تراکنش برای زرین‌پال باید حداقل ۱۰۰ تومان (۱۰۰۰ ریال) باشد.'];
        }

        // Check against gateway currency limits if defined
        $gatewayCurrency = $this->gatewayInfo->currencies()->where('currency', $currency)->first();
        if ($gatewayCurrency) {
            if ($amount < $gatewayCurrency->min_amount || $amount > $gatewayCurrency->max_amount) {
                return ['success' => false, 'message' => "مبلغ تراکنش باید بین $gatewayCurrency->min_amount و $gatewayCurrency->max_amount $currency باشد."];
            }
        }


        $data = [
            "merchant_id" => $this->merchantId,
            "amount" => $amountInRial,
            "currency" => "IRR",
            "callback_url" => $callbackUrl,
            "description" => $description,
            "metadata" => [],
        ];

        if (!empty($additionalData['mobile'])) {
            $data['metadata']['mobile'] = $additionalData['mobile'];
        }
        if (!empty($additionalData['email'])) {
            $data['metadata']['email'] = $additionalData['email'];
        }
        if (!empty($additionalData['order_id'])) { // For linking with an order
            $data['metadata']['order_id'] = $additionalData['order_id'];
        }
         if (!empty($additionalData['user_id'])) { // For linking with a user
            $data['metadata']['user_id'] = $additionalData['user_id'];
        }

        try {
            $response = Http::timeout(30)->post($this->paymentRequestUrl, $data);
            $result = $response->json();

            if ($response->successful() && isset($result['data']['authority']) && $result['data']['code'] == 100) {
                return [
                    'success' => true,
                    'redirect_url' => $this->gatewayUrl . $result['data']['authority'],
                    'payment_id' => $result['data']['authority'], // Authority token
                    'amount_in_rial' => $amountInRial // Store this for verification
                ];
            } else {
                $errorCode = $result['errors']['code'] ?? ($result['data']['code'] ?? 'Unknown');
                $errorMessage = $this->getErrorMessage($errorCode, $result['errors']['message'] ?? ($result['data']['message'] ?? 'خطای ناشناخته از زرین پال'));
                logger()->error('Zarinpal Payment Request Error', ['code' => $errorCode, 'message' => $errorMessage, 'request_data' => $data, 'response_data' => $result]);
                return ['success' => false, 'message' => "خطای API زرین‌پال ({$errorCode}): {$errorMessage}"];
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            logger()->error('Zarinpal Connection Exception on Payment Request', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => "خطا در اتصال به زرین‌پال: " . $e->getMessage()];
        } catch (\Exception $e) {
            logger()->error('Zarinpal General Exception on Payment Request', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => "خطا در درخواست پرداخت: " . $e->getMessage()];
        }
    }

    public function verifyPayment(Request $request, array $storedTransactionData): array
    {
        if (!$this->gatewayInfo || $this->gatewayInfo->status == Status::DISABLE) {
            return ['success' => false, 'message' => 'درگاه زرین‌پال فعال نیست یا پیکربندی نشده است.'];
        }
        if (empty($this->merchantId)) {
            return ['success' => false, 'message' => 'مرچنت کد زرین‌پال پیکربندی نشده است.'];
        }

        $authority = $request->input('Authority');
        $status = $request->input('Status');

        if ($status !== 'OK') {
            $errorMessage = 'پرداخت توسط کاربر لغو شد یا در درگاه با خطا مواجه شد.';
            // Log cancellation:
            logger()->info('Zarinpal Payment Cancelled/Failed by User', ['authority' => $authority, 'status' => $status, 'stored_data' => $storedTransactionData]);
            return ['success' => false, 'message' => $errorMessage, 'status_code' => $status];
        }

        if (empty($authority) || (isset($storedTransactionData['payment_id']) && $authority !== $storedTransactionData['payment_id'])) {
            logger()->warning('Zarinpal Authority Mismatch or Missing', ['request_authority' => $authority, 'stored_authority' => $storedTransactionData['payment_id'] ?? null]);
            return ['success' => false, 'message' => 'توکن تایید (Authority) نامعتبر یا یافت نشد.'];
        }

        // Amount for verification must be the same as sent in requestPayment (in IRR).
        $amountInRial = $storedTransactionData['amount_in_rial'] ?? 0;

        if ($amountInRial <= 0) {
             logger()->error('Zarinpal Verification Error: Invalid amount for verification.', ['amount' => $amountInRial, 'authority' => $authority]);
            return ['success' => false, 'message' => 'مبلغ تراکنش برای تایید نامعتبر است.'];
        }
        $amountInRial = (int)round($amountInRial);

        $data = [
            "merchant_id" => $this->merchantId,
            "authority" => $authority,
            "amount" => $amountInRial,
        ];

        try {
            $response = Http::timeout(30)->post($this->paymentVerificationUrl, $data);
            $result = $response->json();

            if ($response->successful() && isset($result['data']['code'])) {
                $zarinpalCode = $result['data']['code'];
                if ($zarinpalCode == 100) {
                    return [
                        'success' => true,
                        'transaction_id' => $result['data']['ref_id'],
                        'message' => $result['data']['message'] ?? 'پرداخت با موفقیت تایید شد.',
                        'card_pan' => $result['data']['card_pan'] ?? null,
                        'fee' => $result['data']['fee'] ?? null,
                        'fee_type' => $result['data']['fee_type'] ?? null,
                        'verified_at' => now()->toIso8601String(),
                    ];
                } elseif ($zarinpalCode == 101) {
                    logger()->info('Zarinpal Payment Already Verified', ['authority' => $authority, 'ref_id' => $result['data']['ref_id'] ?? 'N/A']);
                    return [
                        'success' => true, // Still a success from payment perspective
                        'transaction_id' => $result['data']['ref_id'] ?? 'N/A',
                        'message' => $this->getErrorMessage($zarinpalCode, $result['data']['message'] ?? 'پرداخت قبلا تایید شده است.'),
                        'card_pan' => $result['data']['card_pan'] ?? null,
                        'fee' => $result['data']['fee'] ?? null,
                        'already_verified' => true
                    ];
                } else {
                    $errorMessage = $this->getErrorMessage($zarinpalCode, $result['data']['message'] ?? 'تایید پرداخت ناموفق بود.');
                    logger()->error('Zarinpal Verification Failed', ['code' => $zarinpalCode, 'message' => $errorMessage, 'authority' => $authority, 'response' => $result]);
                    return ['success' => false, 'message' => "خطای تایید زرین‌پال ({$zarinpalCode}): {$errorMessage}", 'transaction_id' => null, 'status_code' => $zarinpalCode];
                }
            } elseif (isset($result['errors']['code'])) {
                $errorCode = $result['errors']['code'];
                $errorMessage = $this->getErrorMessage($errorCode, $result['errors']['message'] ?? 'تایید پرداخت با خطا مواجه شد.');
                logger()->error('Zarinpal Verification Error Block', ['code' => $errorCode, 'message' => $errorMessage, 'authority' => $authority, 'response' => $result]);
                return ['success' => false, 'message' => "خطای تایید زرین‌پال ({$errorCode}): {$errorMessage}", 'transaction_id' => null, 'status_code' => $errorCode];
            } else {
                $rawResponse = $response->body();
                logger()->error('Zarinpal Verify Unknown Error Structure', ['response_body' => $rawResponse, 'request_data' => $data, 'authority' => $authority]);
                return ['success' => false, 'message' => 'تایید پرداخت با خطای ناشناخته از سمت زرین‌پال مواجه شد.', 'transaction_id' => null];
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            logger()->error('Zarinpal Connection Exception on Verification', ['error' => $e->getMessage(), 'authority' => $authority]);
            return ['success' => false, 'message' => "خطا در اتصال به زرین‌پال برای تایید: " . $e->getMessage(), 'transaction_id' => null];
        } catch (\Exception $e) {
            logger()->error('Zarinpal General Exception on Verification', ['error' => $e->getMessage(), 'authority' => $authority]);
            return ['success' => false, 'message' => "خطا در فرآیند تایید پرداخت: " . $e->getMessage(), 'transaction_id' => null];
        }
    }

    protected function getErrorMessage($code, string $defaultMessage = 'خطایی در عملیات پرداخت رخ داده است.'): string
    {
        $errors = [
            "-1" => "اطلاعات ارسال شده ناقص است.",
            "-2" => "IP یا مرچنت کد پذیرنده صحیح نیست.",
            "-3" => "با توجه به محدودیت‌های شاپرک امکان پرداخت با رقم درخواست شده میسر نیست.",
            "-4" => "سطح تایید پذیرنده پایین‌تر از سطح نقره‌ای است.",
            "-11" => "درخواست مورد نظر یافت نشد.",
            "-12" => "امکان ویرایش درخواست میسر نیست.",
            "-21" => "هیچ نوع عملیات مالی برای این تراکنش یافت نشد.",
            "-22" => "تراکنش ناموفق است.",
            "-33" => "رقم تراکنش با رقم پرداخت شده مطابقت ندارد.",
            "-34" => "سقف تقسیم تراکنش از لحاظ تعداد یا رقم عبور نموده است.",
            "-40" => "اجازه دسترسی به متد مربوطه وجود ندارد.",
            "-41" => "اطلاعات ارسال شده مربوط به AdditionalData نامعتبر است.",
            "-42" => "مدت زمان معتبر طول عمر شناسه پرداخت باید بین ۳۰ دقیقه تا ۴۵ روز باشد.",
            "-54" => "درخواست مورد نظر آرشیو شده است.",
            "100" => "عملیات موفقیت آمیز بود.",
            "101" => "عملیات پرداخت موفق بوده و قبلا عملیات Verify بر روی این تراکنش انجام شده است.",
        ];
        return $errors[(string)$code] ?? $defaultMessage . " (کد خطا: {$code})";
    }
}
