<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CategoryRuleController;
use App\Http\Controllers\Api\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('expenses/export', [ExpenseController::class, 'export']);
    Route::apiResource('expenses', ExpenseController::class)->except('show');

    Route::apiResource('categories', CategoryController::class)->except('show');

    Route::post('rules/apply', [CategoryRuleController::class, 'apply']);
    Route::apiResource('rules', CategoryRuleController::class)->except('show');

    Route::get('budget', [BudgetController::class, 'summary']);
    Route::put('budget/limit', [BudgetController::class, 'updateLimit']);

    Route::get('alerts', [BudgetController::class, 'alerts']);
    Route::post('alerts/read', [BudgetController::class, 'readAllAlerts']);
    Route::post('alerts/{alert}/read', [BudgetController::class, 'readAlert']);
});
