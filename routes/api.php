<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SignInController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\http\Controllers\StaffController;
use App\Http\Controllers\EReceiptController;
use App\Http\Controllers\PushTokenController;
use App\Http\Controllers\AppointmentProductController;
/* 



|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|



| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!

*/

//appointments


Route::middleware('auth:api')->post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::put('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);
    Route::get('/appointments', [AppointmentController::class, 'getAppointments']);
    Route::middleware('auth:sanctum')->post('/cancel-appointment', [AppointmentController::class, 'cancel']);

    
//users
Route::post('/client', [ClientController::class, 'store']);
Route::post('/sign-in', [SignInController::class, 'signIn']);
Route::middleware('auth:sanctum')->get('/profile', [ClientController::class, 'profile']);

//admin
Route::get('/staff-by-category', [CategoryController::class, 'getStaffByCategory']);


Route::get('/service-details', [ServiceController::class, 'getServiceDetails']);
Route::get('/services', [ServiceController::class, 'getServicesByCategory']);

Route::get('/appointments/completed', [ReviewController::class, 'getCompletedAppointments']);
Route::post('/reviews', [ReviewController::class, 'submitReview']);
Route::get('/reviews', [ReviewController::class, 'getReviews']);


Route::get('/appointments/completed-services', [AppointmentController::class, 'getCompletedServices']);
Route::get('/services-by-name', [ServiceController::class, 'getServicesByName']);

Route::post('/reschedule/accept/{id}', [AppointmentController::class, 'acceptReschedule']);

//App
Route::post('/reschedule', [AppointmentController::class, 'reschedule'])->name('reschedule');
Route::post ('/cancel-appointment/{id}', [AppointmentController::class, 'cancelAppointment']);
Route::get('/appointments/check-availability', [AppointmentController::class, 'checkAvailability']);
Route::middleware('auth:sanctum')->put('/appointments/{appointment}/client-reschedule', [AppointmentController::class, 'clientReschedule']);

    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::put('/appointments/{appointmentId}/confirm-reschedule', [NotificationController::class, 'confirmReschedule']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {

    return $request->user();
});

// Appointment Routes
Route::prefix('appointments')->group(function () {
    Route::get('/', [AppointmentController::class, 'getAppointments']);
    Route::post('/', [AppointmentController::class, 'store']);
        Route::put('/appointments/{id}/confirm-reschedule', [AppointmentController::class, 'confirmReschedule']);
    Route::get('/check-availability', [AppointmentController::class, 'checkAvailability']);
    Route::get('/staff-by-category', [AppointmentController::class, 'getStaffByCategory']);
    Route::put('/{id}/status', [AppointmentController::class, 'updateStatus']);
    Route::put('/{id}/reschedule', [AppointmentController::class, 'reschedule']);
    Route::delete('/{id}', [AppointmentController::class, 'destroy']);
});

// Bookmark Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::post('/bookmarks', [BookmarkController::class, 'store']);
    Route::delete('/bookmarks/{id}', [BookmarkController::class, 'destroy']);
    Route::get('/bookmarks/check/{serviceId}', [BookmarkController::class, 'check']);
});

// Notification Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    

    Route::post('/appointments/{appointmentId}/confirm-reschedule', [NotificationController::class, 'confirmReschedule']);
   
});
Route::get('/products', [ProductController::class, 'getProductsForMobile']);
Route::get('/staff/{id}/schedule', [StaffController::class, 'getStaffSchedule']);

Route::middleware('auth:sanctum')->post('/register-push-token', [PushTokenController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    // E-Receipt routes
    Route::get('/receipts', [EReceiptController::class, 'index']);
    Route::get('/receipts/{id}', [EReceiptController::class, 'show']);
    Route::post('/receipts', [EReceiptController::class, 'store']);
    Route::get('/receipts/{id}/download', [EReceiptController::class, 'download']);
    Route::delete('/receipts/{id}', [EReceiptController::class, 'destroy']);
});

// Appointment Products Routes
Route::middleware('auth:sanctum')->group(function () {
    // Store products for an appointment
    Route::post('/appointments/{appointmentId}/products', [AppointmentProductController::class, 'store']);
    
    // Get products for an appointment
    Route::get('/appointments/{appointmentId}/products', [AppointmentProductController::class, 'show']);
    
    // Update product quantity in an appointment
    Route::put('/appointments/{appointmentId}/products/{productId}', [AppointmentProductController::class, 'update']);
    
    // Remove a product from an appointment
    Route::delete('/appointments/{appointmentId}/products/{productId}', [AppointmentProductController::class, 'destroy']);
});