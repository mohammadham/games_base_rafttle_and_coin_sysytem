<?php

use Illuminate\Support\Facades\Route;

Route::namespace('User\Auth')->name('user.')->middleware('guest')->group(function () {
    Route::controller('LoginController')->group(function () {
        Route::get('/login', 'showLoginForm')->name('login');
        Route::post('/login', 'login');
        Route::get('logout', 'logout')->middleware('auth')->withoutMiddleware('guest')->name('logout');
    });

    Route::controller('RegisterController')->group(function () {
        Route::get('register', 'showRegistrationForm')->name('register');
        Route::post('register', 'register');
        Route::post('check-user', 'checkUser')->name('checkUser')->withoutMiddleware('guest');
    });

    Route::controller('ForgotPasswordController')->prefix('password')->name('password.')->group(function () {
        Route::get('reset', 'showLinkRequestForm')->name('request');
        Route::post('email', 'sendResetCodeEmail')->name('email');
        Route::get('code-verify', 'codeVerify')->name('code.verify');
        Route::post('verify-code', 'verifyCode')->name('verify.code');
    });

    Route::controller('ResetPasswordController')->group(function () {
        Route::post('password/reset', 'reset')->name('password.update');
        Route::get('password/reset/{token}', 'showResetForm')->name('password.reset');
    });

    Route::controller('SocialiteController')->group(function () {
        Route::get('social-login/{provider}', 'socialLogin')->name('social.login');
        Route::get('social-login/callback/{provider}', 'callback')->name('social.login.callback');
    });
});

Route::middleware('auth')->name('user.')->group(function () {

    Route::get('user-data', 'User\UserController@userData')->name('data');
    Route::post('user-data-submit', 'User\UserController@userDataSubmit')->name('data.submit');

    //authorization
    Route::middleware('registration.complete')->namespace('User')->controller('AuthorizationController')->group(function () {
        Route::get('authorization', 'authorizeForm')->name('authorization');
        Route::get('resend-verify/{type}', 'sendVerifyCode')->name('send.verify.code');
        Route::post('verify-email', 'emailVerification')->name('verify.email');
        Route::post('verify-mobile', 'mobileVerification')->name('verify.mobile');
    });

    Route::middleware(['check.status', 'registration.complete'])->group(function () {

        Route::namespace('User')->group(function () {

            Route::controller('UserController')->group(function () {
                Route::get('dashboard', 'home')->name('home');
                Route::get('download-attachments/{file_hash}', 'downloadAttachment')->name('download.attachment');

                //Report
                Route::any('deposit/history', 'depositHistory')->name('deposit.history');
                Route::get('transactions', 'transactions')->name('transactions');

                Route::post('add-device-token', 'addDeviceToken')->name('add.device.token');
            });

            //Profile setting
            Route::controller('ProfileController')->group(function () {
                Route::get('profile-setting', 'profile')->name('profile.setting');
                Route::post('profile-setting', 'submitProfile');
                Route::get('change-password', 'changePassword')->name('change.password');
                Route::post('change-password', 'submitPassword');
            });

        // Coin Wallet Routes
        Route::controller(App\Http\Controllers\User\CoinWalletController::class)
            ->prefix('coin-wallet')
            ->name('coin.wallet.')
            ->group(function () {
                Route::get('balances', 'balances')->name('balances');
                Route::get('history', 'history')->name('history');
                Route::get('purchase', 'showPurchaseForm')->name('purchase.form'); // New route for coin purchase form
        });

            Route::prefix('lottery')->name('lottery')->controller('LotteryController')->group(function () {
                Route::get('cart/items', 'cartItems')->name('.cart.items');
                Route::get('purchased', 'purchasedLottery')->name('.purchased');
                Route::get('purchased/detail/{slug}', 'purchasedLotteryDetail')->name('.purchased.detail');
                Route::post('cart/purchase-with-coins', 'purchaseCartWithCoins')->name('.cart.purchase_with_coins');
            });

            // Product Purchase Routes
            Route::controller(App\Http\Controllers\User\ProductPurchaseController::class)
                ->prefix('product-purchase')->name('product.purchase.')
                ->group(function(){
                    Route::post('with-coins/{slug}', 'purchaseWithCoins')->name('with_coins');
                    // Add route for purchasing with gateway if needed later
                    // Route::post('with-gateway/{slug}', 'purchaseWithGateway')->name('with_gateway');
            });
        });

        // Payment
        Route::middleware('ticketAvailabilityCheck')->prefix('deposit')->name('deposit.')->controller('Gateway\PaymentController')->group(function () {
            Route::any('/', 'deposit')->name('index');
            Route::post('insert', 'depositInsert')->name('insert');
            Route::get('confirm', 'depositConfirm')->name('confirm');
            Route::get('manual', 'manualDepositConfirm')->name('manual.confirm');
            Route::post('manual', 'manualDepositUpdate')->name('manual.update');
        });
    });

    // New Payment Gateway Routes (for Zarinpal and other new gateways)
    Route::controller(App\Http\Controllers\User\PaymentController::class)
        ->prefix('gateway-payment')
        ->name('gateway.payment.')
        ->group(function () {
            Route::post('/initiate', 'initiatePayment')->name('initiate');
            Route::match(['get', 'post'], '/callback/{gatewayName}/{trx}', 'paymentCallback')->name('callback');
    });
});
