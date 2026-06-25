<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::post('/orders', [OrderController::class, 'create']);
    Route::get('/orders', [OrderController::class, 'index']);

    Route::middleware('order.owner')->group(function () {
        Route::get('/orders/{id}', [OrderController::class, 'itemsUser']);
        Route::put('/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);
    });

    Route::get('/products', [ProductController::class, 'index']);
});