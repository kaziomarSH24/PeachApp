<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::namespace('App\Http\Controllers')->group(function () {
    Route::post('register', 'AuthController@register');
    Route::post('verify-email', 'AuthController@verifyEmail');
    Route::post('login', 'AuthController@login');
    Route::get('logout', 'AuthController@logout')->middleware('jwt.auth');
    Route::post('resent-otp', 'AuthController@resentOTP');

    //user info
    Route::group([/*'middleware' => 'jwt.auth',*/ 'prefix' => 'user'], function () {
        Route::post('/store-user-info', 'UserController@storeUserInfo');

        //profile
        Route::get('/profile', 'UserController@getProfile');
        Route::post('/store-profile', 'UserController@storeProfile');
        Route::put('/update-profile', 'UserController@updateProfile');
    });
});
