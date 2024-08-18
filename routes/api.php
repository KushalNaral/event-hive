<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\v1\EventCategoryController;
use App\Http\Controllers\v1\Auth\AuthController;
use App\Http\Controllers\v1\Auth\OtpController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {

    //for event-categories
    Route::prefix('event-category')->group(function () {
        Route::get('', [EventCategoryController::class ,'getAllCategories' ])->middleware('auth:api');
        Route::post('/assign', [EventCategoryController::class ,'assignCategoryToUsers' ])->middleware('auth:api');
    });

    //auth routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::prefix('otp')->group(function (){
        Route::post('/verify', [OtpController::class,'verify']);
        Route::post('/resend', [OtpController::class,'resend']);
    });

});


