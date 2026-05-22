<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\User\UserAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Jobs\DailySalesBatchJob;
use App\Jobs\DailySalesBatchJobBad;


    Route::post('register',[UserAuthController::class,'register']);

    Route::post('login',[UserAuthController::class,'login']);

    Route::post('logout', [UserAuthController::class,'logout'])->middleware('auth:sanctum,user-api');

    Route::middleware('auth:sanctum')->get('/User', function (Request $request) {
        return $request->user();
    });

    require __DIR__ .'/admin.php';

    //----------------------------------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

        // products
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);

        // cart
        Route::get('/cart', [CartController::class, 'view_cart']);

        Route::post('/cart/add', [CartController::class, 'add_to_cart']);
        Route::post('/befor-cart/add', [CartController::class, 'befor_add_to_cart']);

        Route::delete('/cart/remove/{cartItem}', [CartController::class, 'delete_from_cart']);

        // orders

        Route::get('/all_orders', [OrderController::class, 'allOrders']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);

        Route::post('/create_order', [OrderController::class, 'createOrder']);
        Route::post('/befor/create_order', [OrderController::class, 'befor_createOrder']);

        route::post('/orders', [OrderController::class, 'createOrder']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']); 



        Route::get('/run-batch-processing', function() {
        \App\Jobs\DailySalesBatchJob::dispatch();
        return "تم بدء المعالجة في الخلفية بنظام الدفعات (Chunks). راقب السجلات!";
        });

        Route::get('/run-bad-processing', function() {
            (new DailySalesBatchJobBad())->handle();
            
            return "تم تشغيل الكود السيئ! افحص سجلات النظام ";
        });

    });

