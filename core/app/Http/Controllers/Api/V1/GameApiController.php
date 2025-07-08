<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApiKey; // To get info about the authenticated API key
use App\Models\User;   // For user operations
use App\Models\ApiTransactionLog; // For logging API transactions
use App\Models\Transaction; // For main financial transactions
use App\Constants\Status;   // For status codes
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // For database transactions if needed

class GameApiController extends Controller
{
    /**
     * A simple endpoint to test API key authentication and connectivity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testConnection(Request $request)
    {
        $apiKeyInstance = $request->attributes->get('apiKeyInstance');

        if ($apiKeyInstance instanceof ApiKey) {
            $this->logApiTransaction($request, $apiKeyInstance, 'test_connection', Status::SUCCESS, 200, ['message' => 'Connection successful.'], null, null, null);
            return response()->json([
                'success' => true,
                'message' => 'API connection successful.',
                'api_key_info' => [
                    'name' => $apiKeyInstance->name,
                    'owner_user_id' => $apiKeyInstance->user_id,
                    'permissions' => $apiKeyInstance->permissions,
                ]
            ]);
        }
        // Fallback, should be caught by middleware ideally
        $this->logApiTransaction($request, null, 'test_connection', Status::FAILURE, 401, ['message' => 'Authentication failed.'], 'AUTH_FAILED', null, null);
        return response()->json(['success' => false, 'message' => 'API authentication failed.'], 401);
    }

    /**
     * Credits coins to a user's account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function creditCoin(Request $request)
    {
        $apiKeyInstance = $request->attributes->get('apiKeyInstance');
        $startTime = microtime(true);

        $validator = Validator::make($request->all(), [
            'user_platform_id' => 'required|string|max:191',
            'game_transaction_id' => 'required|string|max:191',
            'amount' => 'required|numeric|gt:0', // Greater than 0
            'coin_type' => 'required|string|max:50', // e.g., 'gold', 'silver'
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::FAILURE, 422, ['errors' => $validator->errors()], 'VALIDATION_ERROR', null, null, null, $startTime);
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $userPlatformId = $validatedData['user_platform_id'];
        $amount = (float) $validatedData['amount'];
        $coinType = $validatedData['coin_type'];
        $gameTransactionId = $validatedData['game_transaction_id'];
        $description = $validatedData['description'] ?? "Credit by game: " . ($apiKeyInstance->name ?? 'Unknown Game');

        // Placeholder: --- User Identification Logic ---
        // This needs to be implemented based on how user_platform_id maps to your User model.
        // For example, if user_platform_id is stored in a 'platform_id' column in the 'users' table:
        $user = User::where('username', $userPlatformId)->orWhere('email', $userPlatformId)->first(); // Example: find by username or email
        // Or: $user = User::where('external_platform_id', $userPlatformId)->first();

        if (!$user) {
            $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::FAILURE, 404, ['message' => 'User not found.'], 'USER_NOT_FOUND', $userPlatformId, $gameTransactionId, $coinType, $startTime, $amount);
            return response()->json(['success' => false, 'message' => 'User not found with the provided ID.'], 404);
        }

        // Placeholder: --- Coin Crediting Logic & Transaction Management ---
        // This will be more complex with multiple coin types and needs a robust coin/balance management system.
        // For a single coin type (e.g., 'balance' field in User model):
        try {
            DB::beginTransaction();

            // 1. Update user's balance (assuming 'balance' field for default coin type)
            // If you have multiple coin types, this logic will need to fetch/update specific coin balance.
            // For now, let's assume 'balance' is the primary coin.
            if (strtolower($coinType) !== strtolower(gs('cur_text')) && strtolower($coinType) !== 'main') {
                 // For now, only allow crediting the main site currency via this simple setup
                 // Later, a proper coin management system will handle different types.
                $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::FAILURE, 400, ['message' => "Coin type '{$coinType}' not supported for direct credit yet."], 'UNSUPPORTED_COIN_TYPE', $userPlatformId, $gameTransactionId, $coinType, $startTime, $amount);
                DB::rollBack();
                return response()->json(['success' => false, 'message' => "Coin type '{$coinType}' is not supported for direct credit at this time."], 400);
            }

            $user->balance += $amount;
            $user->save();

            // 2. Create a main transaction record
            $platformTrx = getTrx(); // Generate unique platform transaction ID
            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->amount = $amount;
            $transaction->post_balance = $user->balance;
            $transaction->charge = 0; // No charge for API credit by default
            $transaction->trx_type = '+';
            $transaction->details = $description . " (GameTX: {$gameTransactionId})";
            $transaction->trx = $platformTrx;
            $transaction->remark = 'coin_credit_api'; // Or a more specific remark
            $transaction->save();

            DB::commit();

            $responseData = [
                'message' => 'Coins credited successfully.',
                'platform_transaction_id' => $platformTrx,
                'game_transaction_id' => $gameTransactionId,
                'new_balance' => $user->balance, // Current balance of the main coin
            ];
            $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::SUCCESS, 200, $responseData, null, $userPlatformId, $gameTransactionId, $coinType, $startTime, $amount);
            return response()->json(['success' => true] + $responseData);

        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('API CreditCoin Error', [
                'api_key_id' => $apiKeyInstance->id,
                'user_platform_id' => $userPlatformId,
                'error' => $e->getMessage()
            ]);
            $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::FAILURE, 500, ['message' => 'Internal server error during coin credit.'], 'SERVER_ERROR', $userPlatformId, $gameTransactionId, $coinType, $startTime, $amount);
            return response()->json(['success' => false, 'message' => 'An internal error occurred. Please try again later.'], 500);
        }
    }

    // Placeholder for debitCoin - to be implemented with similar structure
    public function debitCoin(Request $request)
    {
        $apiKeyInstance = $request->attributes->get('apiKeyInstance');
        $startTime = microtime(true);
        // Similar validation and logic as creditCoin, but for debiting
        // Ensure to check for sufficient balance before debiting.

        // For now, just a placeholder response:
        $this->logApiTransaction($request, $apiKeyInstance, 'debit_coin', Status::FAILURE, 501, ['message' => 'DebitCoin endpoint is not yet fully implemented.'], 'NOT_IMPLEMENTED', null, null, null, $startTime);
        return response()->json([
            'success' => false, // Mark as false because it's not implemented
            'message' => 'DebitCoin endpoint is not yet fully implemented.',
            'api_key_name' => $apiKeyInstance ? $apiKeyInstance->name : 'N/A',
        ], 501); // 501 Not Implemented
    }

    /**
     * Helper function to log API transactions.
     */
    private function logApiTransaction(
        Request $request, ?ApiKey $apiKeyInstance, string $actionType,
        int $status, int $httpCode, array $responsePayload,
        ?string $errorCode = null, ?string $userPlatformId = null, ?string $gameTransactionId = null,
        ?string $coinType = null, ?float $startTime = null, ?float $amount = null
    ) {
        $duration = $startTime ? (int)((microtime(true) - $startTime) * 1000) : null; // in ms

        ApiTransactionLog::create([
            'api_key_id' => $apiKeyInstance ? $apiKeyInstance->id : null,
            'endpoint_url' => $request->path(),
            'method' => $request->method(),
            'request_payload' => $request->except(['password', 'secret_key', 'api_key']), // Sanitize sensitive data
            'request_headers' => collect($request->headers->all())->map(function ($header) {
                return $header[0] ?? $header; // Get first value of header array
            })->except(['x-api-key', 'x-api-signature', 'authorization']), // Sanitize sensitive headers
            'response_payload' => $responsePayload,
            'response_http_code' => $httpCode,
            'ip_address' => $request->ip(),
            'user_platform_id' => $userPlatformId ?? $request->input('user_platform_id'),
            'game_transaction_id' => $gameTransactionId ?? $request->input('game_transaction_id'),
            'platform_transaction_id' => isset($responsePayload['platform_transaction_id']) ? $responsePayload['platform_transaction_id'] : null,
            'coin_type' => $coinType ?? $request->input('coin_type'),
            'amount' => $amount ?? $request->input('amount'),
            'action_type' => $actionType,
            'status' => $status, // 1 for success, 0 for failure
            'error_message' => $status == Status::FAILURE ? ($responsePayload['message'] ?? ($responsePayload['errors'] ?? null)) : null,
            'error_code' => $errorCode,
            'duration_ms' => $duration,
        ]);
    }
}
