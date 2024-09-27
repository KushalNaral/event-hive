<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\EventRatingController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\UserInteractionsController;
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

    //auth routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('profile', [AuthController::class, 'getProfile'])->middleware('auth:api');


    //for event-categories
    Route::prefix('event-category')->group(function () {
        Route::get('', [EventCategoryController::class ,'getAllCategories' ])->middleware('auth:api');
        Route::post('', [EventCategoryController::class ,'store' ])->middleware('auth:api');
        Route::put('/{id}', [EventCategoryController::class ,'update' ])->middleware('auth:api');
        Route::delete('/{id}', [EventCategoryController::class ,'delete' ])->middleware('auth:api');
        Route::post('/assign', [EventCategoryController::class ,'assignCategoryToUsers' ])->middleware('auth:api');
    });

    //for events
    Route::prefix('events')->middleware('auth:api')->group(function () {
        Route::get('', [EventController::class, 'getAllEvents']);
        Route::get('/{id}', [EventController::class, 'getEventById']);
        Route::post('', [EventController::class, 'createEvents']);
        Route::put('/{id}', [EventController::class, 'updateEventById']);
        Route::delete('/{id}', [EventController::class, 'deleteEventById']);
    });

    // For user interactions
    Route::prefix('event-interactions')->middleware('auth:api')->group(function () {
        Route::get('/', [UserInteractionsController::class, 'getDefaultInteractions']);
        Route::post('/', [UserInteractionsController::class, 'setInteractions']);
        Route::get('/all', [UserInteractionsController::class, 'getAllInteractions']);
        Route::get('/user/{user_id}/events', [UserInteractionsController::class, 'getAllInteractionsForUser']);
        Route::get('/events/{event_id}', [UserInteractionsController::class, 'getInteractionForEventAndUser']);
    });

    // For event ratings
    Route::prefix('event-ratings')->middleware('auth:api')->group(function () {
        Route::get('/', [RatingController::class, 'getAllRatings']);
        Route::post('/', [RatingController::class, 'storeRatings']);
    });

    //otp
    Route::prefix('otp')->group(function (){
        Route::post('/verify', [OtpController::class,'verify']);
        Route::post('/resend', [OtpController::class,'resend']);
    });

});


