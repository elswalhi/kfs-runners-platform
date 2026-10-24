<?php

use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\PaymentController;
use App\Http\Controllers\Api\Admin\RaceKitPriceController;
use App\Http\Controllers\Api\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\Api\PublicRaceController;
use App\Http\Controllers\Api\PublicRegistrationController;
use App\Http\Controllers\Api\EasyKashCallbackController;
use App\Http\Controllers\Api\PublicPaymentStatusController;
use Illuminate\Support\Facades\Route;

Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/races', [PublicRaceController::class, 'index']);
Route::post('/registrations', [PublicRegistrationController::class, 'store'])->middleware('throttle:10,1');
Route::get('/registrations/{registrationCode}/payment-status', [PublicPaymentStatusController::class, 'show'])->middleware('throttle:30,1');
Route::post('/payments/easykash/callback', EasyKashCallbackController::class)->middleware('throttle:120,1');

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/me', [AdminAuthController::class, 'me']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/registrations', [AdminRegistrationController::class, 'index']);
    Route::get('/registrations/{registration}', [AdminRegistrationController::class, 'show']);
    Route::get('/registrations/{registration}/document', [AdminRegistrationController::class, 'document']);
    Route::patch('/registrations/{registration}', [AdminRegistrationController::class, 'update']);
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/race-kit-prices', [RaceKitPriceController::class, 'index']);
    Route::put('/race-kit-prices/{raceKitPrice}', [RaceKitPriceController::class, 'update']);
});
