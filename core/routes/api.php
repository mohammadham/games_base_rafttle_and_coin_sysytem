<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\GameApiController; // Ensure this path is correct based on your actual file structure

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Default Laravel API route (can be removed or used if you use Sanctum/Passport for user auth on other API parts)
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


// API v1 for Games
// All routes in this group will be prefixed with 'api/v1/game'
// and will use the AuthenticateApiKey middleware.
Route::prefix('v1/game')
    ->name('api.v1.game.')
    ->middleware(\App\Http\Middleware\AuthenticateApiKey::class) // Using FQCN for the middleware
    ->group(function () {

        Route::post('/test-connection', [GameApiController::class, 'testConnection'])->name('test.connection');

        // Coin Operations
        Route::post('/credit-coin', [GameApiController::class, 'creditCoin'])->name('credit.coin');
        Route::post('/debit-coin', [GameApiController::class, 'debitCoin'])->name('debit.coin');

        // Optional: Get Balance
        // This endpoint would require a clear way to identify the user securely.
        // If user_platform_id is part of the URL, ensure that the authenticated API key
        // has the rights to query information for that user, or use a user-specific token.
        // Route::get('/balance/{user_platform_id}/{coin_type?}', [GameApiController::class, 'getBalance'])->name('get.balance');

        // Future endpoints for user account linking (OAuth-like flow) would go here.
        // These might have different middleware (e.g., user authentication + API key).
        // Example:
        // Route::post('/connect/initiate', [UserConnectController::class, 'initiateConnection'])->name('connect.initiate');
        // Route::get('/connect/callback', [UserConnectController::class, 'handleCallback'])->name('connect.callback');
});

// A general fallback for unmatched API routes within the /api prefix.
// Note: If you have other API groups (e.g. /api/v2), this might catch them too soon
// if not defined before this fallback.
Route::prefix('api')->fallback(function(){
    return response()->json(['success' => false, 'message' => 'API Endpoint not found.'], 404);
});
