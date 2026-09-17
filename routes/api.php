<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SavingsController;
use App\Http\Controllers\Api\SettlementController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'updatePassword']);

    Route::middleware(['field_officer'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/areas', [AreaController::class, 'index']);

        Route::get('/members/search', [MemberController::class, 'search']);
        Route::get('/members', [MemberController::class, 'index']);
        Route::post('/members', [MemberController::class, 'store']);
        Route::get('/members/{member}', [MemberController::class, 'show']);
        Route::put('/members/{member}', [MemberController::class, 'update']);
        Route::post('/members/{member}/photo', [MemberController::class, 'uploadPhoto']);
        Route::get('/members/{member}/documents', [MemberController::class, 'documents']);
        Route::post('/members/{member}/documents', [MemberController::class, 'storeDocument']);
        Route::delete('/members/{member}/documents/{document}', [MemberController::class, 'destroyDocument']);

        Route::post('/onboarding', [OnboardingController::class, 'store']);

        Route::get('/savings/programs', [SavingsController::class, 'programs']);
        Route::get('/savings/accounts', [SavingsController::class, 'accounts']);
        Route::post('/savings/accounts', [SavingsController::class, 'storeAccount']);
        Route::get('/savings/accounts/member-details/{member}', [SavingsController::class, 'memberDetails']);
        Route::get('/savings/accounts/{account}', [SavingsController::class, 'show']);
        Route::get('/savings/transactions', [SavingsController::class, 'transactions']);

        Route::get('/loans', [LoanController::class, 'index']);
        Route::get('/loans/products', [LoanController::class, 'products']);
        Route::get('/loans/applications/member-details/{member}', [LoanController::class, 'memberDetails']);
        Route::get('/loans/{loan}', [LoanController::class, 'show']);
        Route::get('/loans/{loan}/schedule', [LoanController::class, 'schedule']);
        Route::post('/loans/applications', [LoanController::class, 'storeApplication']);

        Route::get('/collection/savings/{frequency}', [CollectionController::class, 'savingsSheet']);
        Route::get('/collection/loans/{frequency}', [CollectionController::class, 'loanSheet']);
        Route::post('/collection/savings/deposit', [CollectionController::class, 'savingsDeposit']);
        Route::post('/collection/loans/repay', [CollectionController::class, 'loanRepay']);
        Route::post('/collection/sync', [CollectionController::class, 'syncBatch']);

        Route::get('/settlements', [SettlementController::class, 'index']);
        Route::get('/settlements/preview', [SettlementController::class, 'preview']);
        Route::get('/settlements/{settlement}', [SettlementController::class, 'show']);
        Route::post('/settlements', [SettlementController::class, 'store']);

        Route::get('/receipts/{type}/{id}', [ReceiptController::class, 'show']);

        Route::get('/reports/collections', [ReportController::class, 'collections']);
    });
});
