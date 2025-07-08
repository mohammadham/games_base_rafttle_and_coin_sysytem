<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt; // Only if you store secret_key encrypted
// use Illuminate\Support\Facades\Hash; // Only if you were to hash the secret and compare against a client-provided hash (not typical for HMAC)

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKeyHeader = $request->header('X-API-KEY');
        $signatureHeader = $request->header('X-API-SIGNATURE');
        $timestampHeader = $request->header('X-TIMESTAMP');

        if (!$apiKeyHeader) {
            return response()->json(['success' => false, 'message' => 'API Key (X-API-KEY) is missing in headers.'], 401);
        }

        if (!$signatureHeader) {
            return response()->json(['success' => false, 'message' => 'API Signature (X-API-SIGNATURE) is missing in headers.'], 401);
        }

        if (!$timestampHeader) {
            return response()->json(['success' => false, 'message' => 'Timestamp (X-TIMESTAMP) is missing in headers.'], 401);
        }

        $apiKeyInstance = ApiKey::where('api_key', $apiKeyHeader)->active()->first();

        if (!$apiKeyInstance) {
            Log::warning("Invalid, inactive, or expired API Key used: {$apiKeyHeader}");
            return response()->json(['success' => false, 'message' => 'Invalid, inactive, or expired API Key.'], 401);
        }

        // IP Whitelisting Check
        $allowedIps = $apiKeyInstance->allowed_ips; // Accessor returns an array
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
            Log::warning("API Key {$apiKeyHeader} (ID: {$apiKeyInstance->id}) used from disallowed IP: {$request->ip()}", ['allowed_ips' => $allowedIps]);
            return response()->json(['success' => false, 'message' => 'Access from your IP address is not allowed for this API key.'], 403);
        }

        // Check timestamp validity (e.g., within 5 minutes) to prevent replay attacks
        $timestamp = (int) $timestampHeader;
        $currentTime = time();
        // Allow a time window of 5 minutes (300 seconds)
        if (abs($currentTime - $timestamp) > 300) {
            Log::warning("API Key {$apiKeyHeader} (ID: {$apiKeyInstance->id}): Invalid or expired timestamp. Server time: {$currentTime}, Client timestamp: {$timestamp}");
            return response()->json(['success' => false, 'message' => 'Timestamp is invalid, expired, or outside the allowed time window.'], 401);
        }

        // Construct the string to sign
        $requestPath = $request->path();
        $requestMethod = strtoupper($request->method());
        $content = $request->getContent(); // Raw request body

        // String to Sign: Method + Path + Timestamp + Body
        // Ensure this matches exactly how the client generates it.
        // Using a non-printable character like Bell (ASCII 7) or vertical tab (ASCII 11) as separator
        // can sometimes be more robust than newline if body might contain newlines, though newline is common.
        $separator = "\n";
        $stringToSign = $requestMethod . $separator . $requestPath . $separator . $timestamp . $separator . $content;

        // Retrieve the secret key.
        // This example assumes secret_key is stored raw or needs to be decrypted if it was encrypted.
        // If it was shown to user once and then hashed, this logic would be different.
        $secretKey = $apiKeyInstance->secret_key;
        // If $secretKey was encrypted: $secretKey = Crypt::decryptString($apiKeyInstance->secret_key);

        if (empty($secretKey)) {
            Log::error("API Key {$apiKeyHeader} (ID: {$apiKeyInstance->id}): Secret key is missing or not loaded.");
            return response()->json(['success' => false, 'message' => 'Server configuration error: Secret key not found.'], 500);
        }

        $calculatedSignature = hash_hmac('sha256', $stringToSign, $secretKey);

        if (!hash_equals($calculatedSignature, $signatureHeader)) {
            Log::warning("API Key {$apiKeyHeader} (ID: {$apiKeyInstance->id}): Invalid signature.", [
                'string_to_sign_debug' => str_replace("\n", "\\n", $stringToSign), // For easier debugging of newlines
                'content_debug' => $content,
                'expected_signature' => $calculatedSignature,
                'received_signature' => $signatureHeader,
                'path' => $requestPath,
                'method' => $requestMethod,
                'timestamp' => $timestamp,
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid API signature.'], 401);
        }

        // Optional: Permission Check (Example)
        // $currentRouteName = $request->route()->getName(); // e.g., api.v1.game.creditCoin
        // $requiredPermission = $this->mapRouteToPermission($currentRouteName);
        // $apiKeyPermissions = $apiKeyInstance->permissions; // Array from cast
        // if ($requiredPermission && !empty($apiKeyPermissions) && !in_array($requiredPermission, $apiKeyPermissions)) {
        //     Log::warning("API Key {$apiKeyHeader} (ID: {$apiKeyInstance->id}) does not have permission '{$requiredPermission}' for route '{$currentRouteName}'.");
        //     return response()->json(['success' => false, 'message' => 'Permission denied for this action.'], 403);
        // }

        // Attach the APIKey instance and user (if applicable) to the request
        $request->attributes->add(['apiKeyInstance' => $apiKeyInstance]);
        if ($apiKeyInstance->user) { // If the API key is associated with a specific user on your platform
            $request->attributes->add(['apiKeyOwner' => $apiKeyInstance->user]);
        }

        // Increment usage count and update last_used_at (consider if this should be done only on success or for all valid auth attempts)
        // Doing it here means it's counted even if a later business logic step fails.
        // If you want to count only "successful API calls", move this to a terminable middleware or after $next($request) and check response status.
        $apiKeyInstance->increment('usage_count');
        $apiKeyInstance->last_used_at = now();
        $apiKeyInstance->save();

        return $next($request);
    }

    /**
     * Optional: Helper to map route names to permission strings.
     * This is just an example and needs to be adapted to your routing and permission scheme.
     */
    // protected function mapRouteToPermission($routeName)
    // {
    //     $map = [
    //         'api.v1.game.creditCoin' => 'coin_credit',
    //         'api.v1.game.debitCoin' => 'coin_debit',
    //         'api.v1.game.getBalance' => 'coin_balance_read',
    //     ];
    //     return $map[$routeName] ?? null;
    // }
}
