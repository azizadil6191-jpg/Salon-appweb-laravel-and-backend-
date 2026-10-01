<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CashierProductsController;
use App\Http\Controllers\CashierAppointmentsController;
use App\Http\Controllers\Manager\InventoryController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\Manager\AttendanceController;




/*
|----------------------------------------------------------------------
| Web Routes
|----------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
// Route::get('/update-past-appointments', [AppointmentController::class, 'updatePastAppointments']);

// Route::get('/current-time', function () {
//     return now()->toDateTimeString();
// });


Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin', function() {
    return redirect()->route('admin.login');
});

Route::get('/manager', function () {
    return redirect()->route('manager.login'); // Redirect to the login page
});


Route::get('/staff', function () {
    return redirect()->route('staff.login'); // Redirect to the login page
});

Route::get('/owner', function () {
    return redirect()->route('owner.login'); // Redirect to the login page
});

Route::get('/cashier', function () {
    return redirect()->route('cashier.login'); // Redirect to the login page
});


//admin
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminController::class, 'login'])->name('admin.login');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/category-sales', [AdminController::class, 'getCategorySales'])->name('admin.category-sales');
    Route::get('/monthly-revenue', [AdminController::class, 'getMonthlyRevenue'])->name('admin.monthly-revenue');
    Route::get('/weekly-performance', [AdminController::class, 'getWeeklyPerformance'])->name('admin.weekly-performance');;
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/product-sales-over-time', [AdminController::class, 'getProductSalesOverTime']);
    Route::get('/sales-comparison', [AdminController::class, 'getSalesComparison']);
    
    // Staff management routes
    Route::middleware(['auth:admin'])->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
        Route::patch('/staff/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
        Route::patch('/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset-password');
        Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::resource('clients', ClientController::class)->except(['edit', 'update']);
    });
    
    // Services routes
    Route::get('/services/{id}/edit', [ServiceController::class, 'edit'])->name('services.edit');
    Route::put('/services/{id}', [ServiceController::class, 'update'])->name('services.update');
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::delete('/services/{id}', [ServiceController::class, 'destroy'])->name('services.destroy');

    // Other resource routes
    Route::resource('owners', OwnerController::class);
    Route::resource('managers', ManagerController::class);
    Route::resource('cashiers', CashierController::class);

    //Appointments
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::put('admin/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
    Route::put('/admin/appointments/{id}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.updateStatus');
    Route::put('/admin/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.updateStatus');
    Route::put('/appointments/{id}/reject', [AppointmentController::class, 'reject'])->name('appointments.reject');
    Route::get('/api/staff-by-category', [AppointmentController::class, 'getStaffByCategory']);

    //Products
    Route::get('/products', [ProductController::class, 'index'])->name('admin.products.index');
    Route::post('/admin/products', [ProductController::class, 'store'])->name('admin.products.store');
    Route::get('/admin/products/{product}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/admin/products/{product}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/admin/products/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
    Route::patch('/admin/products/update-stocks', [ProductController::class, 'updateStocks'])->name('admin.products.update-stocks');

    Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');
    Route::post('/clients/{id}/block', [AdminController::class, 'blockClient'])->name('admin.block-client');
    Route::post('/clients/{id}/unblock', [AdminController::class, 'unblockClient'])->name('admin.unblock-client');

    Route::post('/admin/notes/{note}/mark-as-read', [NoteController::class, 'markAsRead'])->name('admin.notes.mark-as-read');

    // Admin Staff Schedule Routes
    Route::get('/admin/staff/{staffId}/schedule', [AdminController::class, 'viewStaffSchedule'])->name('admin.staff.schedule');
    Route::put('/admin/staff/{staffId}/schedule', [AdminController::class, 'updateStaffSchedule'])->name('admin.staff.schedule.update');


     Route::get('/admin/history', [App\Http\Controllers\AdminController::class, 'history'])->name('admin.history');
  
});

//manager
Route::prefix('manager')->group(function () {
    Route::get('/login', [ManagerController::class, 'showLoginForm'])->name('manager.login');
    Route::post('/login', [ManagerController::class, 'login'])->name('manager.login.submit');
    Route::post('/logout', [ManagerController::class, 'logout'])->name('manager.logout');
    Route::get('/category-sales', [ManagerController::class, 'getCategorySales'])->name('manager.category-sales');
    Route::get('/monthly-revenue', [ManagerController::class, 'getMonthlyRevenue'])->name('manager.monthly-revenue');
    Route::get('/weekly-performance', [ManagerController::class, 'getWeeklyPerformance'])->name('manager.weekly-performance');
    Route::get('/sales-comparison', [ManagerController::class, 'getSalesComparison']);
    
    Route::resource('clients', ClientController::class);
    Route::resource('staff', StaffController::class);

    Route::middleware('auth:manager')->group(function () {
        Route::get('/dashboard', [ManagerController::class, 'dashboard'])->name('manager.dashboard');
        Route::get('/dashboard/products', [ManagerController::class, 'dashboardProducts'])->name('manager.dashboard.products');
        Route::get('/appointments', [AppointmentController::class, 'managerIndex'])->name('manager.appointments');
        Route::get('/clients', [ClientController::class, 'managerIndex'])->name('manager.clients');
        Route::get('/staff', [StaffController::class, 'managerIndex'])->name('manager.staff'); // List all staff
        Route::get('/staff/create', [StaffController::class, 'managerCreate'])->name('staff.create'); // Create staff form
        Route::get('/history', [ManagerController::class, 'history'])->name('manager.history');
        Route::get('/services-report', [ManagerController::class, 'servicesReport'])->name('manager.services-report');

        Route::get('/services', [ServiceController::class, 'managerIndex'])->name('manager.services');
        Route::post('/manager/services', [ServiceController::class, 'store'])->name('manager.services.store');

        Route::get('/services/{id}/edit', [ServiceController::class, 'managerEdit'])->name('manager.services.edit');
        Route::put('/services/{id}', [ServiceController::class, 'managerUpdate'])->name('manager.services.update');
        Route::delete('/services/{id}', [ServiceController::class, 'managerDestroy'])->name('manager.services.destroy');

        Route::get('/getReviewforManager', [ReviewController::class, 'getReviewforManager'])->name('manager.reviews');
        Route::get('/schedule', [ManagerController::class, 'schedule'])->name('manager.schedule');
        Route::post('/schedule', [ManagerController::class, 'storeSchedule'])->name('manager.store-schedule');
        Route::get('/weekly-schedule', [ManagerController::class, 'viewWeeklySchedule'])->name('manager.weekly-schedule');
        Route::get('/check-day-off-conflicts/{staffId}/{day}', [ManagerController::class, 'checkDayOffConflicts']);
        Route::get('/day-off-requests', [ManagerController::class, 'dayOffRequests'])->name('manager.day-off-requests');
        Route::patch('/day-off-requests/{id}', [ManagerController::class, 'updateDayOffRequest'])->name('manager.update-day-off-request');
        Route::get('/manager/inventory', [ManagerController::class, 'inventory'])->name('manager.inventory');
        Route::post('/manager/products', [ManagerController::class, 'storeProduct'])->name('manager.products.store');
        Route::get('/manager/products/{product}/edit', [ManagerController::class, 'editProduct'])->name('manager.products.edit');
        Route::put('/manager/products/{product}', [ManagerController::class, 'updateProduct'])->name('manager.products.update');
        Route::delete('/manager/products/{product}', [ManagerController::class, 'deleteProduct'])->name('manager.products.delete');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
        Route::post('/products/add-stocks', [InventoryController::class, 'addStock'])->name('products.add-stocks');
        Route::post('/products/use-stock', [InventoryController::class, 'useStock'])->name('products.use-stock');
        Route::post('/products/store', [InventoryController::class, 'store'])->name('manager.products.store');

        Route::post('/manager/notes', [NoteController::class, 'store'])->name('manager.notes.store');
        Route::post('/manager/notes/{note}/mark-as-read', [NoteController::class, 'markAsRead'])->name('manager.notes.markAsRead');

        // Attendance routes
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('manager.attendance');
        Route::get('/attendance/filter', [AttendanceController::class, 'filter'])->name('manager.attendance.filter');
        Route::get('/attendance/export', [AttendanceController::class, 'export'])->name('manager.attendance.export');

        // Calendar appointments route
        Route::get('/get-all-appointments', [ManagerController::class, 'getAllAppointmentsForCalendar'])->name('manager.get-all-appointments');

        // Report route
        Route::get('/report', [ManagerController::class, 'report'])->name('manager.report');

        Route::get('/product-sales-report', [ManagerController::class, 'productSalesReport'])->name('manager.product-sales-report');
        Route::get('/product-sales-report/export-csv', [ManagerController::class, 'exportProductSalesCSV'])->name('manager.product-sales-report.export-csv');

        Route::get('/manager/inventory-report', [ManagerController::class, 'inventoryReport'])->name('manager.inventory-report');

        Route::post('/products/record-consumable', [App\Http\Controllers\ManagerController::class, 'recordConsumable'])->name('products.record-consumable');
        Route::get('/products/consumable-history', [App\Http\Controllers\ManagerController::class, 'getConsumableHistory'])->name('products.consumable-history');
    });

    // Inventory routes

});


//staff
Route::prefix('staff')->group(function () {
    Route::get('/login', [StaffController::class, 'showLoginForm'])->name('staff.login');
    Route::post('/login', [StaffController::class, 'login'])->name('staff.login.submit');
    Route::post('/logout', [StaffController::class, 'logout'])->name('staff.logout');

    Route::middleware('auth:staff')->group(function () {
        Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('staff.dashboard');
        Route::get('/schedule', [StaffController::class, 'viewSchedule'])->name('staff.schedule');
        Route::get('/request-day-off', [StaffController::class, 'requestDayOff'])->name('staff.request-day-off');
        Route::post('/request-day-off', [StaffController::class, 'storeDayOffRequest'])->name('staff.store-day-off-request');
        Route::get('/appointments', [StaffController::class, 'viewAppointments'])->name('staff.appointments');
        Route::put('/appointments/{id}/status', [AppointmentController::class, 'acceptOrDeclineAppointment'])->name('appointments.staff.status');
        Route::get('/getReviewforStaff', [ReviewController::class, 'getReviewforStaff'])->name('staff.reviews');
        
        // Attendance routes
        Route::get('/attendance', [StaffController::class, 'attendance'])->name('staff.attendance');
        Route::post('/time-in', [StaffController::class, 'timeIn'])->name('staff.time-in');
        Route::post('/time-out', [StaffController::class, 'timeOut'])->name('staff.time-out');
        Route::get('/myattendance', [StaffController::class, 'myAttendance'])->name('staff.myattendance');
    });
});

//owner
Route::prefix('owner')->group(function () {
    Route::get('/login', [OwnerController::class, 'showLoginForm'])->name('owner.login');
    Route::post('/login', [OwnerController::class, 'login'])->name('owner.login.submit');
    Route::post('/logout', [OwnerController::class, 'logout'])->name('owner.logout');
    Route::get('/category-sales', [OwnerController::class, 'getCategorySales'])->name('manager.category-sales');
    Route::get('/monthly-revenue', [OwnerController::class, 'getMonthlyRevenue'])->name('manager.monthly-revenue');
    Route::get('/weekly-performance', [OwnerController::class, 'getWeeklyPerformance'])->name('manager.weekly-performance');
    
 

    Route::middleware('auth:owner')->group(function () {
        Route::get('/dashboard', [OwnerController::class, 'dashboard'])->name('owner.dashboard');
        Route::get('/staff-schedules', [OwnerController::class, 'staffSchedules'])->name('owner.staff-schedules');
        Route::get('/inventory', [OwnerController::class, 'inventory'])->name('owner.inventory');
        Route::get('/sales-report', [OwnerController::class, 'salesReport'])->name('owner.sales-report');
        Route::get('/appointmentreports', [OwnerController::class, 'appointmentreports'])->name('owner.appointmentreports');
    });
});

//cashier
Route::prefix('cashier')->group(function () {
    Route::get('/login', [CashierController::class, 'showLoginForm'])->name('cashier.login');
    Route::post('/login', [CashierController::class, 'login'])->name('cashier.login.submit');
    Route::post('/logout', [CashierController::class, 'logout'])->name('cashier.logout');


    Route::middleware('auth:cashier')->group(function () {
        Route::get('/dashboard', [CashierController::class, 'dashboard'])->name('cashier.dashboard');
        Route::get('/products', [CashierProductsController::class, 'index'])->name('cashier.products');
        Route::post('/products', [CashierProductsController::class, 'store'])->name('cashier.products.store');
        Route::get('/products/{product}/edit', [CashierProductsController::class, 'edit'])->name('cashier.products.edit');
        Route::put('/products/{product}', [CashierProductsController::class, 'update'])->name('cashier.products.update');
        Route::delete('/products/{product}', [CashierProductsController::class, 'destroy'])->name('cashier.products.destroy');
        
        Route::get('/transaction', [CashierProductsController::class, 'transaction'])->name('cashier.transaction');
        Route::post('/checkout', [CashierProductsController::class, 'checkout'])->name('cashier.checkout');
        Route::get('/history', [CashierProductsController::class, 'history'])->name('cashier.history');
        Route::get('/stocksview', [CashierProductsController::class, 'stocksview'])->name('cashier.stocks');
        Route::post('/products/add-stock', [CashierProductsController::class, 'addStock'])->name('cashier.products.addStock');
        
        // Appointment routes
        Route::get('/appointments', [CashierAppointmentsController::class, 'index'])->name('cashier.appointments');
        Route::get('/appointments/all', [CashierAppointmentsController::class, 'getAllAppointments'])->name('cashier.appointments.all');
        Route::patch('/appointments/{id}/status', [CashierAppointmentsController::class, 'updateStatus'])->name('cashier.appointments.updateStatus');
        Route::post('/appointments/{id}/reject', [CashierAppointmentsController::class, 'reject'])->name('cashier.appointments.reject');
        Route::post('/appointments/{id}/reschedule', [CashierAppointmentsController::class, 'reschedule'])->name('cashier.appointments.reschedule');
        Route::post('/appointments/{id}/accept-reschedule', [CashierAppointmentsController::class, 'acceptReschedule'])->name('cashier.appointments.acceptReschedule');
        Route::delete('/appointments/{id}', [CashierAppointmentsController::class, 'delete'])->name('cashier.appointments.delete');
        Route::get('/appointments/staff-by-service/{serviceId}', [CashierAppointmentsController::class, 'getStaffByService']);
        Route::post('/appointments/{id}/no-show', [CashierController::class, 'markAsNoShow'])->name('appointments.markAsNoShow');

        Route::get('/appointments/{id}/ereceipt', [CashierAppointmentsController::class, 'getEReceipt'])->name('cashier.appointments.ereceipt');
        
    });
});

// Staff routes
Route::middleware(['auth:admin'])->group(function () {
    Route::get('/admin/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('/admin/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::put('/admin/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/admin/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
    Route::patch('/admin/staff/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
    Route::patch('/admin/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset-password');
});

Route::get('/manager/attendance/filter', [AttendanceController::class, 'filter'])->name('manager.attendance.filter');

Route::get('/get-current-time', function () {
    return response()->json([
        'time' => now()->format('h:i:s A'),
        'isoTime' => now()->toIso8601String()
    ]);
});



