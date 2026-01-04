<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class ExchangeRateService
{
    /**
     * دریافت نرخ ارز با اولویت:
     * 1. Cache
     * 2. API داینامیک
     * 3. نرخ استاتیک (Fallback)
     *
     * @param string|null $apiUrl URL سرویس نرخ ارز
     * @param string|null $jsonPath مسیر پارامتر در JSON (مثال: data.price یا rate)
     * @param float $fallbackRate نرخ استاتیک در صورت عدم دسترسی به API
     * @param int $cacheMinutes مدت زمان کش (دقیقه)
     * @return array ['rate' => float, 'source' => string]
     */
    public static function getRate(
        ?string $apiUrl = null,
        ?string $jsonPath = null,
        float $fallbackRate = 60000,
        int $cacheMinutes = 30
    ): array {
        $cacheKey = 'exchange_rate_usd_irt_' . md5($apiUrl ?? 'default');

        // 1. اول از Cache چک کن
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return [
                'rate' => (float) $cachedData['rate'],
                'source' => 'cache',
                'cached_at' => $cachedData['cached_at'] ?? null
            ];
        }

        // 2. تلاش برای دریافت از API
        if ($apiUrl) {
            try {
                $response = Http::timeout(10)->get($apiUrl);
                
                if ($response->successful()) {
                    $data = $response->json();
                    $rate = self::extractRateFromJson($data, $jsonPath);
                    
                    if ($rate && $rate > 0) {
                        // ذخیره در Cache
                        Cache::put($cacheKey, [
                            'rate' => $rate,
                            'cached_at' => now()->toDateTimeString()
                        ], now()->addMinutes($cacheMinutes));

                        Log::info('Exchange rate fetched from API', [
                            'url' => $apiUrl,
                            'rate' => $rate
                        ]);

                        return [
                            'rate' => (float) $rate,
                            'source' => 'api',
                            'api_url' => $apiUrl
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch exchange rate from API', [
                    'url' => $apiUrl,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 3. استفاده از نرخ Fallback
        Log::info('Using fallback exchange rate', ['rate' => $fallbackRate]);
        
        return [
            'rate' => (float) $fallbackRate,
            'source' => 'fallback'
        ];
    }

    /**
     * استخراج نرخ از JSON بر اساس مسیر
     * پشتیبانی از nested paths مثل: data.price, result.rate, usd.sell
     *
     * @param array $data
     * @param string|null $path
     * @return float|null
     */
    private static function extractRateFromJson(array $data, ?string $path): ?float
    {
        if (empty($path)) {
            // اگر مسیر مشخص نشده، سعی کن مقادیر رایج را پیدا کنی
            $commonKeys = ['rate', 'price', 'value', 'sell', 'usd', 'USD'];
            foreach ($commonKeys as $key) {
                if (isset($data[$key]) && is_numeric($data[$key])) {
                    return (float) $data[$key];
                }
            }
            return null;
        }

        // استخراج با مسیر nested (مثال: data.usd.sell)
        $value = Arr::get($data, $path);
        
        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * تبدیل مبلغ از ارز پایه به تومان
     *
     * @param float $amount مبلغ به ارز پایه (معمولاً USD)
     * @param float $rate نرخ تبدیل
     * @param string $targetCurrency ارز مقصد (IRT یا IRR)
     * @return int مبلغ به تومان یا ریال (عدد صحیح)
     */
    public static function convertToIranianCurrency(
        float $amount,
        float $rate,
        string $targetCurrency = 'IRT'
    ): int {
        $converted = $amount * $rate;

        // اگر ریال خواسته شده، ضربدر 10 کن
        if (strtoupper($targetCurrency) === 'IRR') {
            $converted *= 10;
        }

        // گرد کردن به عدد صحیح (درگاه‌های ایرانی اعشار نمی‌پذیرند)
        return (int) round($converted);
    }

    /**
     * پاک کردن Cache نرخ ارز
     *
     * @param string|null $apiUrl
     * @return void
     */
    public static function clearCache(?string $apiUrl = null): void
    {
        $cacheKey = 'exchange_rate_usd_irt_' . md5($apiUrl ?? 'default');
        Cache::forget($cacheKey);
    }
}
