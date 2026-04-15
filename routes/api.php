<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\MonthCloseController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\MenuEntryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\UserController;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', DashboardController::class)->middleware('permission:view dashboard');
    Route::post('/close-month', MonthCloseController::class)->middleware('permission:view dashboard');
    Route::post('/unlock-month', \App\Http\Controllers\Api\MonthUnlockController::class)->middleware('permission:view dashboard');
    Route::get('/expense-categories', fn () => ExpenseCategory::query()->where('is_active', true)->orderBy('type')->orderBy('name')->get());
    Route::apiResource('users', UserController::class)->middleware('permission:manage users');
    Route::apiResource('members', MemberController::class)->middleware('permission:manage members');
    Route::apiResource('expenses', ExpenseController::class)->middleware('permission:manage expenses');
    Route::apiResource('menu-entries', MenuEntryController::class)->middleware('permission:manage menu');
    Route::apiResource('payments', PaymentController::class)->middleware('permission:manage payments');
});
