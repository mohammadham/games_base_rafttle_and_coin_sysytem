<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApiKey;
use App\Models\User;
use App\Models\ApiTransactionLog;
use App\Models\Transaction; // Main financial transactions
use App\Models\CoinType;
use App\Models\UserCoinBalance;
use App\Constants\Status;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameApiController extends Controller
{
    /**
     * Test API connection and authentication.
     */
    public function testConnection(Request $request)
    {
        $apiKeyInstance = $request->attributes->get('apiKeyInstance');
        $startTime = microtime(true);

        if ($apiKeyInstance instanceof ApiKey) {
            $this->logApiTransaction($request, $apiKeyInstance, 'test_connection', Status::SUCCESS, 200, ['message' => 'Connection successful.'], null, null, null, null, $startTime);
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
        // This part should ideally not be reached if middleware is working correctly
        $this->logApiTransaction($request, null, 'test_connection', Status::FAILURE, 401, ['message' => 'Authentication failed.'], 'AUTH_FAILED', null, null, null, $startTime);
        return response()->json(['success' => false, 'message' => 'API authentication failed.'], 401);
    }

    /**
     * Credits coins to a user's account.
     */
    public function creditCoin(Request $request)
    {
        $apiKeyInstance = $request->attributes->get('apiKeyInstance');
        $startTime = microtime(true);

        $validator = Validator::make($request->all(), [
            'user_platform_id' => 'required|string|max:191', // Identifier for the user on the game's side or a known identifier on our platform
            'game_transaction_id' => 'required|string|max:191', // Unique transaction ID from the game
            'amount' => 'required|numeric|gt:0',
            'coin_code' => 'nullable|string|max:50', // Code of the coin type (e.g., GOLD, SILVER). If null, assume base coin.
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::FAILURE, 422, ['errors' => $validator->errors()->toArray()], 'VALIDATION_ERROR', null, null, $request->input('coin_code'), $startTime, (float)$request->input('amount'));
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $userPlatformId = $validatedData['user_platform_id'];
        $amount = (float) $validatedData['amount'];
        $coinCode = $validatedData['coin_code'] ?? null;
        $gameTransactionId = $validatedData['game_transaction_id'];
        $description = $validatedData['description'] ?? "Coin credit from game: " . ($apiKeyInstance->name ?? 'N/A');

        // --- User Identification Logic ---
        // IMPORTANT: This is a simplified placeholder.
        // A robust system would use a pre-established link between the game user and the platform user,
        // possibly via an OAuth-like flow or a stored mapping.
        // For now, we'll try to find the user by a common identifier like username or email.
        $user = User::where('username', $userPlatformId)
                    ->orWhere('email', $userPlatformId)
                    // ->orWhere('external_game_user_id', $userPlatformId) // If you add such a field
                    ->first();

        if (!$user) {
            $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::FAILURE, 404, ['message' => 'User not found.'], 'USER_NOT_FOUND', $userPlatformId, $gameTransactionId, $coinCode, $startTime, $amount);
            return response()->json(['success' => false, 'message' => 'User not found with the provided identifier.'], 404);
        }

        // --- Coin Type Identification ---
        $coinTypeToCredit = null;
        if ($coinCode) {
            $coinTypeToCredit = CoinType::where('code', strtoupper($coinCode))->active()->first();
            if (!$coinTypeToCredit) {
                $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::FAILURE, 400, ['message' => "Coin type '{$coinCode}' not found or inactive."], 'COIN_TYPE_NOT_FOUND', $userPlatformId, $gameTransactionId, $coinCode, $startTime, $amount);
                return response()->json(['success' => false, 'message' => "Invalid or inactive coin type: {$coinCode}."], 400);
            }
        } else {
            $coinTypeToCredit = CoinType::getBaseCoin();
            if (!$coinTypeToCredit) {
                 $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::FAILURE, 500, ['message' => 'Base coin type not configured on the platform.'], 'BASE_COIN_MISSING', $userPlatformId, $gameTransactionId, 'BASE', $startTime, $amount);
                return response()->json(['success' => false, 'message' => 'Base coin type not configured. Please contact support.'], 500);
            }
        }

        // --- Prevent Duplicate Game Transactions ---
        // Check if this game_transaction_id has already been processed for this api_key_id
        $existingLog = ApiTransactionLog::where('api_key_id', $apiKeyInstance->id)
                                        ->where('game_transaction_id', $gameTransactionId)
                                        ->where('action_type', 'credit_coin') // Check specifically for credit
                                        ->where('status', Status::SUCCESS) // Only for successful ones
                                        ->exists();
        if ($existingLog) {
            $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::FAILURE, 409, ['message' => 'Duplicate game transaction ID.'], 'DUPLICATE_GAME_TXN', $userPlatformId, $gameTransactionId, $coinTypeToCredit->code, $startTime, $amount);
            return response()->json(['success' => false, 'message' => 'This game transaction has already been processed.'], 409); // 409 Conflict
        }


        // --- Coin Crediting Logic ---
        try {
            $platformTrx = getTrx(); // Unique ID for CoinTransaction table

            $userCoinBalance = UserCoinBalance::getOrCreateBalance($user->id, $coinTypeToCredit->id);

            $transactionParams = [
                'trx' => $platformTrx,
                'details' => $description . " (GameTX: {$gameTransactionId}, API Key: {$apiKeyInstance->name})",
                'game_api_key_id' => $apiKeyInstance->id,
                // 'related_transactionable_id' => $apiLog->id, // Link to the API log entry after it's created
                // 'related_transactionable_type' => ApiTransactionLog::class,
            ];

            // This will also create a CoinTransaction entry
            $coinTransaction = $userCoinBalance->credit($amount, 'api_game_credit', $transactionParams);

            // Log this API call
            $responseData = [
                'message' => 'Coins credited successfully.',
                'platform_transaction_id' => $platformTrx,
                'game_transaction_id' => $gameTransactionId,
                'user_platform_id' => $userPlatformId,
                'coin_type_credited' => $coinTypeToCredit->code,
                'amount_credited' => $amount,
                'new_balance' => $userCoinBalance->fresh()->balance,
            ];
            $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::SUCCESS, 200, $responseData, null, $userPlatformId, $gameTransactionId, $coinTypeToCredit->code, $startTime, $amount, $platformTrx);

            // Optionally link CoinTransaction back to ApiTransactionLog if needed (e.g. for easier lookup)
            // This requires saving the ApiTransactionLog first, getting its ID, then updating CoinTransaction.
            // Or, more simply, the ApiTransactionLog can store the CoinTransaction's trx_id.

            return response()->json(['success' => true] + $responseData);

        } catch (\InvalidArgumentException $e) {
            Log::error('API CreditCoin Argument Error', ['api_key_id' => $apiKeyInstance->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::FAILURE, 400, ['message' => $e->getMessage()], 'INVALID_ARGUMENT', $userPlatformId, $gameTransactionId, $coinTypeToCredit->code, $startTime, $amount);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) { // Handles InsufficientBalanceException if it were a debit
            Log::error('API CreditCoin Runtime Error', ['api_key_id' => $apiKeyInstance->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::FAILURE, 400, ['message' => $e->getMessage()], 'RUNTIME_ERROR', $userPlatformId, $gameTransactionId, $coinTypeToCredit->code, $startTime, $amount);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) { // Catch any other throwable
            DB::rollBack(); // Ensure rollback if a DB transaction was started in credit/debit and failed here
            Log::critical('API CreditCoin Critical Error', ['api_key_id' => $apiKeyInstance->id, 'user_id' => $user->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->logApiTransaction($request, $apiKeyInstance, 'credit_coin', Status::FAILURE, 500, ['message' => 'Internal server error during coin credit.'], 'SERVER_ERROR', $userPlatformId, $gameTransactionId, $coinTypeToCredit->code, $startTime, $amount);
            return response()->json(['success' => false, 'message' => 'An internal error occurred. Please try again later.'], 500);
        }
    }

    public function debitCoin(Request $request)
    {
        $apiKeyInstance = $request->attributes->get('apiKeyInstance');
        $startTime = microtime(true);

        $validator = Validator::make($request->all(), [
            'user_platform_id' => 'required|string|max:191',
            'game_transaction_id' => 'required|string|max:191',
            'amount' => 'required|numeric|gt:0',
            'coin_code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->logApiTransaction($request, $apiKeyInstance, 'debit_coin', Status::FAILURE, 422, ['errors' => $validator->errors()->toArray()], 'VALIDATION_ERROR', null, null, $request->input('coin_code'), $startTime, (float)$request->input('amount'));
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $userPlatformId = $validatedData['user_platform_id'];
        $amount = (float) $validatedData['amount'];
        $coinCode = $validatedData['coin_code'] ?? null;
        $gameTransactionId = $validatedData['game_transaction_id'];
        $description = $validatedData['description'] ?? "Coin debit from game: " . ($apiKeyInstance->name ?? 'N/A');

        $user = User::where('username', $userPlatformId)->orWhere('email', $userPlatformId)->first();
        if (!$user) {
            $this->logApiTransaction($request, $apiKeyInstance, 'debit_coin', Status::FAILURE, 404, ['message' => 'User not found.'], 'USER_NOT_FOUND', $userPlatformId, $gameTransactionId, $coinCode, $startTime, $amount);
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $coinTypeToDebit = null;
        if ($coinCode) {
            $coinTypeToDebit = CoinType::where('code', strtoupper($coinCode))->active()->first();
            if (!$coinTypeToDebit) {
                $this->logApiTransaction($request, $apiKeyInstance, 'debit_coin', Status::FAILURE, 400, ['message' => "Coin type '{$coinCode}' not found or inactive."], 'COIN_TYPE_NOT_FOUND', $userPlatformId, $gameTransactionId, $coinCode, $startTime, $amount);
                return response()->json(['success' => false, 'message' => "Invalid or inactive coin type: {$coinCode}."], 400);
            }
        } else {
            $coinTypeToDebit = CoinType::getBaseCoin();
            if (!$coinTypeToDebit) {
                $this->logApiTransaction($request, $apiKeyInstance, 'debit_coin', Status::FAILURE, 500, ['message' => 'Base coin type not configured.'], 'BASE_COIN_MISSING', $userPlatformId, $gameTransactionId, 'BASE', $startTime, $amount);
                return response()->json(['success' => false, 'message' => 'Base coin type not configured.'], 500);
            }
        }

        $existingLog = ApiTransactionLog::where('api_key_id', $apiKeyInstance->id)
                                        ->where('game_transaction_id', $gameTransactionId)
                                        ->where('action_type', 'debit_coin')
                                        ->where('status', Status::SUCCESS)
                                        ->exists();
        if ($existingLog) {
            $this->logApiTransaction($request, $apiKeyInstance, 'debit_coin', Status::FAILURE, 409, ['message' => 'Duplicate game transaction ID.'], 'DUPLICATE_GAME_TXN', $userPlatformId, $gameTransactionId, $coinTypeToDebit->code, $startTime, $amount);
            return response()->json(['success' => false, 'message' => 'This game transaction has already been processed.'], 409);
        }

        try {
            $platformTrx = getTrx();
            $userCoinBalance = UserCoinBalance::getOrCreateBalance($user->id, $coinTypeToDebit->id);

            $transactionParams = [
                'trx' => $platformTrx,
                'details' => $description . " (GameTX: {$gameTransactionId}, API Key: {$apiKeyInstance->name})",
                'game_api_key_id' => $apiKeyInstance->id,
            ];

            // This will throw a RuntimeException if balance is insufficient
            $coinTransaction = $userCoinBalance->debit($amount, 'api_game_debit', $transactionParams);

            $responseData = [
                'message' => 'Coins debited successfully.',
                'platform_transaction_id' => $platformTrx,
                'game_transaction_id' => $gameTransactionId,
                'user_platform_id' => $userPlatformId,
                'coin_type_debited' => $coinTypeToDebit->code,
                'amount_debited' => $amount,
                'new_balance' => $userCoinBalance->fresh()->balance,
            ];
            $this->logApiTransaction($request, $apiKeyInstance, 'debit_coin', Status::SUCCESS, 200, $responseData, null, $userPlatformId, $gameTransactionId, $coinTypeToDebit->code, $startTime, $amount, $platformTrx);
            return response()->json(['success' => true] + $responseData);

        } catch (\InvalidArgumentException $e) {
            Log::error('API DebitCoin Argument Error', ['api_key_id' => $apiKeyInstance->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->logApiTransaction($request, $apiKeyInstance, 'debit_coin', Status::FAILURE, 400, ['message' => $e->getMessage()], 'INVALID_ARGUMENT', $userPlatformId, $gameTransactionId, $coinTypeToDebit->code, $startTime, $amount);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) { // Catches InsufficientBalanceException from debit method
            Log::warning('API DebitCoin Runtime Error (e.g. Insufficient Balance)', ['api_key_id' => $apiKeyInstance->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->logApiTransaction($request, $apiKeyInstance, 'debit_coin', Status::FAILURE, 402, ['message' => $e->getMessage()], 'INSUFFICIENT_BALANCE', $userPlatformId, $gameTransactionId, $coinTypeToDebit->code, $startTime, $amount); // 402 Payment Required
            return response()->json(['success' => false, 'message' => $e->getMessage()], 402);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::critical('API DebitCoin Critical Error', ['api_key_id' => $apiKeyInstance->id, 'user_id' => $user->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->logApiTransaction($request, $apiKeyInstance, 'debit_coin', Status::FAILURE, 500, ['message' => 'Internal server error during coin debit.'], 'SERVER_ERROR', $userPlatformId, $gameTransactionId, $coinTypeToDebit->code, $startTime, $amount);
            return response()->json(['success' => false, 'message' => 'An internal error occurred. Please try again later.'], 500);
        }
    }

    private function logApiTransaction(
        Request $request, ?ApiKey $apiKeyInstance, string $actionType,
        int $status, int $httpCode, array $responsePayload,
        ?string $errorCode = null, ?string $userPlatformId = null, ?string $gameTransactionId = null,
        ?string $coinType = null, ?float $startTime = null, ?float $amount = null, ?string $platformTrxId = null
    ) {
        $duration = $startTime ? (int)((microtime(true) - $startTime) * 1000) : null;

        // Sanitize sensitive headers more carefully
        $headersToLog = [];
        $excludedHeaders = ['x-api-key', 'x-api-signature', 'authorization', 'cookie', 'set-cookie'];
        foreach($request->headers->all() as $name => $values){
            if(!in_array(strtolower($name), $excludedHeaders)){
                $headersToLog[$name] = $values[0] ?? $values;
            }
        }

        $logEntry = [
            'api_key_id' => $apiKeyInstance ? $apiKeyInstance->id : null,
            'endpoint_url' => $request->path(),
            'method' => $request->method(),
            'request_payload' => $request->except(['password', 'secret_key', 'api_key', '_token']),
            'request_headers' => $headersToLog,
            'response_payload' => $responsePayload,
            'response_http_code' => $httpCode,
            'ip_address' => $request->ip(),
            'user_platform_id' => $userPlatformId ?? $request->input('user_platform_id'),
            'game_transaction_id' => $gameTransactionId ?? $request->input('game_transaction_id'),
            'platform_transaction_id' => $platformTrxId ?? ($responsePayload['platform_transaction_id'] ?? null),
            'coin_type' => $coinType ?? $request->input('coin_code'), // Use coin_code from request if available
            'amount' => $amount ?? (is_numeric($request->input('amount')) ? (float)$request->input('amount') : null),
            'action_type' => $actionType,
            'status' => $status,
            'error_message' => $status == Status::FAILURE ? json_encode($responsePayload['errors'] ?? ($responsePayload['message'] ?? null)) : null,
            'error_code' => $errorCode,
            'duration_ms' => $duration,
        ];

        try {
            ApiTransactionLog::create($logEntry);
        } catch (\Exception $e) {
            Log::error('Failed to create API transaction log', ['error' => $e->getMessage(), 'log_data' => $logEntry]);
        }
    }
}
