<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Contracts\Permission;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['middleware'=>'api',
              'prefix'=>'auth'
],function($router){

Route:: post("user-signup","App\Http\Controllers\UserController@userSignUp");

Route::post("user-login","App\Http\Controllers\UserController@userLogin");

Route::post("user-logout", "App\Http\Controllers\UserController@userLogout");

Route::post("email-verify","App\Http\Controllers\UserController@verifyEmail");

Route::post("resend-pin","App\Http\Controllers\UserController@resendPin");

Route::post("user-forgotpassword","App\Http\Controllers\UserController@forgotPassword");

Route::post("user-verify-pin","App\Http\Controllers\UserController@verifyPin");

Route::post("user-reset-password","App\Http\Controllers\UserController@resetPassword");

Route::post("user-reservation","App\Http\Controllers\UserController@storeReservation");

Route::post("update-reservation/{user_id}","App\Http\Controllers\UserController@updateReservation");

Route::post("show-ticket","App\Http\Controllers\UserController@showPendingReservation");

Route::post("show-receipt","App\Http\Controllers\UserController@showReceipt");

Route::post("make-payment","App\Http\Controllers\PaymentController@makePayment");

});



Route::group(['middleware'=>'api',
              'prefix'=>'admin'
],function($router){


    Route:: post("admin-signup","App\Http\Controllers\AdminController@adminSignUp");

    Route::post("admin-login", "App\Http\Controllers\AdminController@adminLogin");

    Route::post("admin-logout", "App\Http\Controllers\AdminController@adminLogout");

    Route::post("admin-email-verify","App\Http\Controllers\AdminController@adminVerifyEmail");

    Route::post("admin-resend-pin","App\Http\Controllers\AdminController@adminResendPin");

    Route::post("admin-forgotpassword","App\Http\Controllers\AdminController@adminForgotPassword");

     Route::post("admin-pin-verify","App\Http\Controllers\AdminController@adminPinVerify");
    // Route::post("admin-verify-pin","App\Http\Controllers\AdminController@adminVerifyPin");

    Route::post("admin-reset-password","App\Http\Controllers\AdminController@adminResetPassword");

    Route::post("admin-create-sys-user","App\Http\Controllers\AdminController@adminCreateSystemUser");

    Route::put("admin-update-sys-user","App\Http\Controllers\AdminController@adminUpdateSystem");

    Route::post("admin-create-categories","App\Http\Controllers\AdminController@createCategory");

    Route::post("admin-show-categories","App\Http\Controllers\AdminController@showCategory");

    Route::post("admin-add-price","App\Http\Controllers\PricingController@createPrice");

    Route::post("admin-show-price","App\Http\Controllers\PricingController@showPriceList");

    Route::post("admin-update-price/{id}","App\Http\Controllers\PricingController@updatePriceList");

    Route::post("admin-create-roles","App\Http\Controllers\RoleController@createRole");

    Route::post("admin-update-roles/{id}","App\Http\Controllers\RoleController@updateRole");

    Route::post("admin-create-permission","App\Http\Controllers\PermissionController@createPermission");

    Route::post("admin-update-permission/{id}","App\Http\Controllers\PermissionController@updatePermission");

    Route::post("admin-show-sys-users","App\Http\Controllers\AdminController@showSystemUsers");

    Route::post("admin-delete-sys-user/{id}","App\Http\Controllers\AdminController@deleteSystemUser");

    Route::post("admin-get-users","App\Http\Controllers\DashboardController@getAllUser");

    Route::post("admin-get-ticket","App\Http\Controllers\DashboardController@getNumberOfTickets7");

    Route::post("admin-get-sales","App\Http\Controllers\DashboardController@getTicketAmount7");

    Route::post("admin-get-transaction","App\Http\Controllers\DashboardController@getTransaction7");

});





Route::group(['middleware'=>'api',['role:ticket admin'],
              'prefix'=>'manager'
],function($router){

Route::post("ticket-manager-login", "App\Http\Controllers\ManagerController@ticketManagerLogin");

Route::post("ticket-manager-checkuser", "App\Http\Controllers\ManagerController@checkUserTicket");

Route::post("ticket-manager-decline/{id}", "App\Http\Controllers\ManagerController@updateUserTicketDeclined");

Route::post("ticket-manager-approve/{id}", "App\Http\Controllers\ManagerController@updateUserTicketAprroved");

Route::post("ticket-manager-logout", "App\Http\Controllers\ManagerController@ticketManagerLogout");

});




Route::group(['middleware'=>['api','role:reservation admin'],
// ['permission:Create appointment'],
              'prefix'=>'resmanager'
],function($router){

Route::post("reservation-manager-login", "App\Http\Controllers\ReservationManagerController@reservationManagerLogin");

});

// Route::group(['middleware'=>'auth:api',
//     'prefix'=>'user'
// ],function($router){

// Route::post("user-reservation","App\Http\Controllers\TicketController@storeReservation");

// });
