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
    // Route::put('update-password', 'AuthController@updatePassword')->middleware('jwt.auth');

    //user info
    Route::group(['middleware' => 'jwt.auth', 'prefix' => 'user'], function () {
        Route::get('/get-user-info', 'UserController@getUserInfo');
        Route::post('/store-user-info', 'UserController@storeUserInfo');

        //profile
        Route::get('/profile', 'UserController@getProfile');
        Route::post('/store-profile', 'UserController@storeProfile');
        Route::put('/update-profile', 'UserController@updateProfile');

        //home controller
        Route::get('/get-nearby-users', 'HomeController@getNearbyUsers');

        //match controller
        Route::post('/handle-interaction', 'InteractionController@handleInteraction');
        Route::get('/get-matches', 'InteractionController@getMatches');

        //conversation controller
        Route::get('/get-contact', 'ConversationController@getContact');
        Route::post('/send-message', 'ConversationController@sendMessage');
        Route::put('/mark-as-read/{conversationId}', 'ConversationController@markAsRead');
        Route::get('/get-messages/{conversationId}', 'ConversationController@getMessages');

        //settings controller
        Route::post('/settings', 'SettingsController@settings');
        Route::get('/get-settings', 'SettingsController@getSettings');
        Route::put('/update-avatar', 'SettingsController@updateAvatar');
        Route::put('/update-password', 'SettingsController@updatePassword');

    });
});

