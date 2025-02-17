<?php

use App\Http\Controllers\Api\NotificationController;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::namespace('App\Http\Controllers')->group(function () {
    Route::post('register', 'AuthController@register');
    Route::post('verify-email', 'AuthController@verifyEmail');
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout')->middleware('jwt.auth');
    Route::post('resent-otp', 'AuthController@resentOTP');
    //check token
    Route::get('validate-token', 'AuthController@validateToken');
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
        Route::get('/user-details/{id}', 'HomeController@userDetails');

        //match controller
        Route::post('/handle-interaction', 'InteractionController@handleInteraction');
        Route::get('/get-matches', 'InteractionController@getMatches');

        //blocked controller
        Route::get('/blocked-user', 'BlockedController@getBlockedUsers');
        Route::post('/blocked-user', 'BlockedController@blockedUser');
        Route::post('/unblocked-user', 'BlockedController@unBlocked');

        //conversation controller
        Route::get('/get-contact', 'ConversationController@getContact');
        Route::post('/send-message', 'ConversationController@sendMessage');
        Route::put('/mark-as-read/', 'ConversationController@markAsRead');
        Route::get('/get-messages/{conversationId}', 'ConversationController@getMessages');

        //settings controller
        Route::post('/settings', 'SettingsController@settings');
        Route::get('/get-settings', 'SettingsController@getSettings');
        Route::put('/update-avatar', 'SettingsController@updateAvatar');
        Route::put('/update-password', 'SettingsController@updatePassword');

    });


    //admin controller
    Route::group(['middleware' => ['jwt.auth','admin'], 'prefix' => 'admin'], function () {
        Route::get('/dashboard', 'Admin\DashboardController@dashboard');
        Route::get('/users', 'Admin\DashboardController@users');
        Route::get('/user/{id}', 'Admin\DashboardController@user');
        Route::put('/update-user-status/{id}', 'Admin\DashboardController@updateUserStatus');
        Route::delete('/delete-user/{id}', 'Admin\DashboardController@deleteUser');

        //faq controller
        Route::get('/faqs', 'Admin\FAQController@faqs');
        Route::post('/faqs/store', 'Admin\FAQController@store');
        Route::put('/faqs/update/{id}', 'Admin\FAQController@update');
        Route::delete('/faqs/delete/{id}', 'Admin\FAQController@delete');

        //terms and conditions controller
        Route::get('/terms-and-conditions', 'Admin\TremsAndConditionController@termsAndConditions');
        Route::post('/terms-and-conditions/store', 'Admin\TremsAndConditionController@store');

        //admin controller
        Route::get('/admin-information', 'Admin\AdminController@getAdminProfile');
        Route::post('/admin-information/update', 'Admin\AdminController@updateAdmin');
    });
});

Route::group(['middleware' => 'jwt.auth', 'prefix' => 'notifications'], function () {
    Route::get('/', [NotificationController::class, 'getNotifications']);
    Route::put('/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::put('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/delete', [NotificationController::class, 'deleteNotification']);
});

// Route::namespace('App\Http\Controllers')->group(function () {
//     Route::group(['middleware' => 'jwt.auth', 'prefix'], function () {
//         Route::get('/notifications', 'NotificationController@getNotifications');
//         Route::put('/mark-as-read', 'NotificationController@markAsRead');
//         Route::put('/mark-all-as-read', 'NotificationController@markAllAsRead');
//         Route::delete('/delete', 'NotificationController@deleteNotification');
//     });
// });

//for testing route
// Route::get('/test', function () {
//     $userPreferences = Setting::where('user_id', 60)->first();
//          if($userPreferences->is_push_notify){
//             return "true";
//          }else{
//                 return "false";
//          }

// });

 //socket user status update api
 Route::post('/update-status', function (Request $request) {
    $user = User::find($request->userId);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found.'
        ], 404);
    }
    $user->is_active = $request->is_active;
    $user->save();
    return response()->json([
        'success' => true,
        'message' => 'User status updated successfully.'
    ]);
});
