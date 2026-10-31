<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\AuthController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/member-photos/{filename}', [DashboardController::class, 'memberPhoto'])->name('members.photo');
Route::get('/reports', [DashboardController::class, 'report'])->name('reports.index');
Route::get('/transactions', [DashboardController::class, 'transactions'])->name('transactions.index');
Route::post('/transactions', [DashboardController::class, 'storeTransaction'])->name('transactions.store');
Route::put('/transactions/{transaction}', [DashboardController::class, 'updateTransaction'])->name('transactions.update');
Route::delete('/transactions/{transaction}', [DashboardController::class, 'destroyTransaction'])->name('transactions.destroy');
Route::get('/members', [DashboardController::class, 'members'])->name('members.index');
Route::get('/members/cards', [DashboardController::class, 'memberCards'])->name('members.cards');
Route::get('/members/{member}/card', [DashboardController::class, 'memberCard'])->name('members.card');
Route::get('/payments', [DashboardController::class, 'payments'])->name('payments.index');
Route::post('/payments', [DashboardController::class, 'storePayment'])->name('payments.store');
Route::delete('/payments/{payment}', [DashboardController::class, 'destroyPayment'])->name('payments.destroy');
Route::post('/members', [DashboardController::class, 'storeMember'])->name('members.store');
Route::put('/members/{member}', [DashboardController::class, 'updateMember'])->name('members.update');
Route::delete('/members/{member}', [DashboardController::class, 'destroyMember'])->name('members.destroy');
Route::get('/settings/fee', [SettingController::class, 'edit'])->name('settings.fee');
Route::put('/settings/fee', [SettingController::class, 'updateFee'])->name('settings.fee.update');
});
