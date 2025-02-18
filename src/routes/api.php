<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    // Sipariş 
    Route::apiResource('orders', OrderController::class);
    Route::get('orders/{order}/discounts', [OrderController::class, 'calculateDiscounts']);

    /* İndirim kuralları */
    Route::prefix('discount-rules')->group(function () 
    {
        Route::get('/', [DiscountRuleController::class, 'index']);
        Route::post('/', [DiscountRuleController::class, 'store']);
        Route::delete('/{categoryId}', [DiscountRuleController::class, 'destroy']);
    });
});
