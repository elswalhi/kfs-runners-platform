<?php

use App\Http\Controllers\Api\PublicPromoCodeController;
use App\Http\Controllers\Web\AdminWebAuthController;
use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');
Route::get('/register', [PublicSiteController::class, 'register'])->name('register');
Route::post('/register', [PublicSiteController::class, 'storeRegistration'])->middleware('throttle:10,1')->name('register.store');
Route::post('/promo-codes/validate', [PublicPromoCodeController::class, 'validateCode'])->middleware('throttle:30,1')->name('promo-codes.validate');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminWebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminWebAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AdminWebAuthController::class, 'logout'])->name('logout');
        Route::get('/', [AdminWebController::class, 'dashboard'])->name('dashboard');
        Route::get('/export-overview', [AdminWebController::class, 'exportOverview'])->name('export-overview');
        Route::get('/registrations', [AdminWebController::class, 'registrations'])->name('registrations');
        Route::get('/registrations/export', [AdminWebController::class, 'exportRegistrations'])->name('registrations.export');
        Route::get('/registrations/{registration}', [AdminWebController::class, 'registration'])->name('registrations.show');
        Route::patch('/registrations/{registration}', [AdminWebController::class, 'updateRegistration'])->name('registrations.update');
        Route::get('/registrations/{registration}/document', [AdminWebController::class, 'registrationDocument'])->name('registrations.document');
        Route::get('/payments', [AdminWebController::class, 'payments'])->name('payments');
        Route::get('/races', [AdminWebController::class, 'races'])->name('races');
        Route::post('/races', [AdminWebController::class, 'storeRace'])->name('races.store');
        Route::put('/races/{race}', [AdminWebController::class, 'updateRace'])->name('races.update');
        Route::delete('/races/{race}', [AdminWebController::class, 'deleteRace'])->name('races.destroy');
        Route::get('/pricing', [AdminWebController::class, 'pricing'])->name('pricing');
        Route::post('/pricing', [AdminWebController::class, 'storePrice'])->name('pricing.store');
        Route::put('/pricing/{raceKitPrice}', [AdminWebController::class, 'updatePrice'])->name('pricing.update');
        Route::delete('/pricing/{raceKitPrice}', [AdminWebController::class, 'deletePrice'])->name('pricing.destroy');
        Route::get('/promo-codes', [AdminWebController::class, 'promoCodes'])->name('promo-codes');
        Route::post('/promo-codes', [AdminWebController::class, 'storePromoCode'])->name('promo-codes.store');
        Route::patch('/promo-codes/{promoCode}/toggle', [AdminWebController::class, 'togglePromoCode'])->name('promo-codes.toggle');
        Route::delete('/promo-codes/{promoCode}', [AdminWebController::class, 'deletePromoCode'])->name('promo-codes.destroy');
        Route::get('/team-import', [AdminWebController::class, 'teamImport'])->name('team-import');
        Route::post('/team-import', [AdminWebController::class, 'importTeam'])->middleware('throttle:10,1')->name('team-import.store');
    });
});
