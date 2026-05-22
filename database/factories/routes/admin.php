<?php


use App\Http\Controllers\Admin\AdminAuthController;

use App\Http\Controllers\BookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function (){

    Route::post('register',[AdminAuthController::class,'register']);
    Route::post('login',[AdminAuthController::class,'login']);

    Route::middleware('auth:sanctum,admin-api')->group( function () {
        Route::post('logout', [AdminAuthController::class,'logout']) ;
    });
});
