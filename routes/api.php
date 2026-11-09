<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

Route::get('/products', [ProductController::class, 'list']);
Route::get('/products/{id}', [ProductController::class, 'det']);

Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::post('/products/{id}/reviews', [ReviewController::class, 'rate']);
    
    Route::prefix('/cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/{id}', [CartController::class, 'add']);
        Route::delete('/{id}', [CartController::class, 'delete']);
    });

    Route::prefix('/orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{id}', [OrderController::class, 'det']);
        Route::post('/', [OrderController::class, 'buy']);
    });

    Route::prefix('admin')->middleware(['admin'])->group(function () {
        Route::prefix('/categories')->group(function () {
            Route::get('/', [CategoryController::class, 'list']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('/{id}', [CategoryController::class, 'edit']);
            Route::delete('/{id}', [CategoryController::class, 'delete']);
            Route::post('/reorder', [CategoryController::class, 'reorder']);
        });
        Route::prefix('/products')->group(function () {
            Route::get('/', [ProductController::class, 'crud']);
            Route::post('/', [ProductController::class, 'store']);
            Route::put('/{id}', [ProductController::class, 'edit']);
            Route::delete('/{id}', [ProductController::class, 'delete']);
        });
    });
});
