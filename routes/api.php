<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DecorationController;
use App\Http\Controllers\HallController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\HallServiceController;
use App\Http\Controllers\PublicPartyController;
use App\Http\Controllers\WalletController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//Route::post('register/users', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('send-verification-code', [AuthController::class, 'sendCode']);
Route::post('verify-email', [AuthController::class, 'verifyEmail']);
Route::post('forget_password', [AuthController::class, 'sendResetToken']);
Route::post('reset_password', [AuthController::class, 'resetPassword']);
Route::get('getMyProfile', [AuthController::class, 'getMyProfile'])->middleware('auth:sanctum');
Route::get('show/profile/{id}/{type}', [AuthController::class, 'showUserProfile']);
//Route::post('updateProfile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
Route::post('updateProfile/provider', [AuthController::class, 'updateProfileProvider'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::post('updateProfile/customer', [AuthController::class, 'updateProfileCustomer'])->middleware('auth:sanctum');


//Function For Provider
Route::post('providers/register', [ProviderController::class, 'register']);
Route::get('providers/approved', [ProviderController::class, 'getApprovedProviders'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::get('providers/unapproved', [ProviderController::class, 'getUnapprovedProviders'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::post('provider/assign', [ProviderController::class, 'assignMyServices'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('provider/show/{providerId}', [ProviderController::class, 'showProvider'])->middleware('auth:sanctum');
Route::get('providers/index', [ProviderController::class, 'indexProviders'])->middleware('auth:sanctum');
Route::post('providers/import', [ProviderController::class, 'importProviders'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('provider/profile', [ProviderController::class, 'getMyProfile']);
Route::get('/providers/{providerId}/posts', [ProviderController::class, 'getProviderPosts'])// هاد لعرض بوستات بروفايدر معين
;

//Function Approve by Admin
Route::put('providers/approveOrActive', [AdminController::class, 'approveProvider'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::put('users/active', [AdminController::class, 'activateUser'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::put('halls/approve', [AdminController::class, 'approveHall'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::put('foods/approve', [AdminController::class, 'approveFood'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::put('decorations/approve', [AdminController::class, 'approveDecoration'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::put('publicParties/approve', [AdminController::class, 'approvePublicParties'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::delete('providers/delete/{id}', [AdminController::class, 'deleteProvider'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::delete('users/delete/{id}', [AdminController::class, 'deleteUser'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::patch('providers/{id}/suspend', [AdminController::class, 'suspendProvider'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::patch('users/{id}/suspend', [AdminController::class, 'suspendUser'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::get('/admin/approved-posts', [AdminController::class, 'getAllApprovedPosts'])->middleware('auth:sanctum')->middleware('CheckAdmin');// هاد عرض كل البوستات المقبولة للادمن;



//Function For Customers
Route::post('customers/register',  [CustomerController::class, 'register']);



//Function For Halls
Route::get('halls/inside', [HallController::class, 'HallInside']);
Route::get('halls/outside', [HallController::class, 'HallOutside']);
Route::get('halls/unapproved', [HallController::class, 'getUnapprovedHalls'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::get('halls/approved', [HallController::class, 'getApprovedHalls'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::post('halls/store', [HallController::class, 'store'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('get/MyHallsFalse', [HallController::class, 'MyHallsFalse'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('get/MyHallsTrue', [HallController::class, 'MyHallsTrue'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::post('halls/{providerId?}', [HallController::class, 'filterHalls']);// هاد للفلترة
Route::delete('hall/delete/{hallId}', [HallController::class, 'deleteHall'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('hall/show/{hallId}', [HallController::class, 'showHall'])->middleware('auth:sanctum');
Route::get('hall/index', [HallController::class, 'indexHalls'])->middleware('auth:sanctum');
Route::get('hall/{providerId}', [HallController::class, 'providerHalls'])->middleware('auth:sanctum');
Route::get('halls/{hallId}/available-days', [HallController::class, 'availableDays'])->middleware('auth:sanctum');
Route::get('halls/{hallId}/available-hours/{date}', [HallController::class, 'availableHours'])->middleware('auth:sanctum');
Route::post('halls/{hallId}/availability', [HallController::class, 'addAvailability'])->middleware('auth:sanctum')->middleware('CheckProvider');

//Function For Hall Services
Route::post('hallService/store', [HallServiceController::class, 'store'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::post('hallService/delete', [HallServiceController::class, 'destroy'])->middleware('auth:sanctum')->middleware('CheckProvider');

//Function For Foods
Route::get('foods/unapproved', [FoodController::class, 'getUnapprovedFoods'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::get('foods/approved', [FoodController::class, 'getApprovedFoods'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::get('get/MyFoodsFalse', [FoodController::class, 'MyFoodsFalse'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('get/MyFoodsTrue', [FoodController::class, 'MyFoodsTrue'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::post('food/store', [FoodController::class, 'store'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::delete('food/delete/{foodId}', [FoodController::class, 'deleteFood'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::post('foods/{providerId?}', [FoodController::class, 'filterFoods']); // هاد فلترة 
Route::get('food/show/{foodId}', [FoodController::class, 'showFood']);
Route::get('food/index', [FoodController::class, 'indexFoods']);
Route::get('food/{providerId}', [FoodController::class, 'providerFoods']);

//Function For Public Parties
Route::get('party/unapproved', [PublicPartyController::class, 'getUnapprovedPublicParties'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::get('party/approved', [PublicPartyController::class, 'getApprovedPublicParties'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::get('get/MyPublicPartiesFalse', [PublicPartyController::class, 'MyPublicPartiesFalse'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('get/MyPublicPartiesTrue', [PublicPartyController::class, 'MyPublicPartiesTrue'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::post('publicParties/store', [PublicPartyController::class, 'store'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::delete('publicParties/delete/{partyId}', [PublicPartyController::class, 'deleteParty'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::post('publicParties/{providerId?}', [PublicPartyController::class, 'filterParties']);// هاد فلترة 
Route::get('publicParties/show/{partyId}', [PublicPartyController::class, 'showParty']);
Route::get('publicParties/index', [PublicPartyController::class, 'indexParties']);
Route::get('publicParties/{providerId}', [PublicPartyController::class, 'providerParties']);


//Function For Decoration
Route::get('decorations/unapproved', [DecorationController::class, 'getUnapprovedDecorations'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::get('decorations/approved', [DecorationController::class, 'getApprovedDecorations'])->middleware('auth:sanctum')->middleware('CheckAdmin');
Route::post('decorations/store', [DecorationController::class, 'store'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('get/MyDecorationsFalse', [DecorationController::class, 'MyDecorationsFalse'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('get/MyDecorationsTrue', [DecorationController::class, 'MyDecorationsTrue'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::delete('decoration/delete/{decorationId}', [DecorationController::class, 'deleteDecoration'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::post('decorations/{providerId?}', [DecorationController::class, 'filterDecorations']);// هاد فلترة 
Route::get('decoration/show/{decorationId}', [DecorationController::class, 'showDecoration']);
Route::get('decoration/index', [DecorationController::class, 'indexDecorations']);
Route::get('decoration/{providerId}', [DecorationController::class, 'providerDecorations']);
Route::get('/occasions', [DecorationController::class, 'indexOccasions']);

//Function For Booking
//Hall Booking
Route::post('hall-bookings', [BookingController::class, 'bookHall'])->middleware('auth:sanctum');
Route::patch('availability/{hallId}/block', [BookingController::class, 'blockAvailability'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::patch('availability/{hallId}/unblock', [BookingController::class, 'unblockAvailability'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('provider/hall-bookings', [BookingController::class, 'providerHallBookings'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('hall-bookings/my-bookings', [BookingController::class, 'customerHallBookings'])->middleware('auth:sanctum');
Route::patch('hall-bookings/{id}/approve', [BookingController::class, 'approveHallBooking'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::patch('hall-bookings/{id}/reject', [BookingController::class, 'rejectHallBooking'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::patch('hall-bookings/{id}/cancel', [BookingController::class, 'cancelHallBooking'])->middleware('auth:sanctum');
// Food Booking
Route::post('food-bookings', [BookingController::class, 'bookFood'])->middleware('auth:sanctum');
Route::get('provider/food-bookings', [BookingController::class, 'providerFoodBookings'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('food-bookings/my-bookings', [BookingController::class, 'customerFoodBookings'])->middleware('auth:sanctum');
Route::patch('food-bookings/{id}/approve', [BookingController::class, 'approveFoodBooking'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::patch('food-bookings/{id}/reject', [BookingController::class, 'rejectFoodBooking'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::patch('food-bookings/{id}/confirm-payment', [BookingController::class, 'confirmFoodPayment'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::patch('food-bookings/{id}/cancel', [BookingController::class, 'cancelFoodBooking'])->middleware('auth:sanctum');
// Decoration Booking
Route::post('decoration-bookings', [BookingController::class, 'bookDecoration'])->middleware('auth:sanctum');
Route::get('provider/decoration-bookings', [BookingController::class, 'providerDecorationBookings'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('decoration-bookings/my-bookings', [BookingController::class, 'customerDecorationBookings'])->middleware('auth:sanctum');
Route::patch('decoration-bookings/{id}/approve', [BookingController::class, 'approveDecorationBooking'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::patch('decoration-bookings/{id}/reject', [BookingController::class, 'rejectDecorationBooking'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::patch('decoration-bookings/{id}/confirm-payment', [BookingController::class, 'confirmDecorationPayment'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::patch('decoration-bookings/{id}/cancel', [BookingController::class, 'cancelDecorationBooking'])->middleware('auth:sanctum');
// Public Party Booking
Route::post('party-bookings', [BookingController::class, 'bookPublicParty'])->middleware('auth:sanctum');
Route::get('provider/party-bookings', [BookingController::class, 'providerPartyBookings'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::get('party-bookings/my-bookings', [BookingController::class, 'customerPartyBookings'])->middleware('auth:sanctum');
Route::patch('party-bookings/{id}/confirm-payment', [BookingController::class, 'confirmPartyPayment'])->middleware('auth:sanctum')->middleware('CheckProvider');
Route::patch('party-bookings/{id}/cancel', [BookingController::class, 'cancelPartyBooking'])->middleware('auth:sanctum');
Route::get('provider/all-bookings', [BookingController::class, 'providerAllBookings'])->middleware('auth:sanctum')->middleware('CheckProvider');// لعرض كل الحجزات لبروفايدر معين
Route::get('provider/bookings', [BookingController::class, 'providerBookingsByStatus'])->middleware('auth:sanctum')->middleware('CheckProvider');// عرض كل الحجوزات لبروفايدر معين حسب حالة الحجر


/////              Wallet Routes              ///////
Route::middleware('auth:customer')->group(function () {
    Route::get('/wallet',[WalletController::class, 'myWallet']);
    Route::get( '/wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('/wallet/deposit', [WalletController::class, 'deposit'] );
});