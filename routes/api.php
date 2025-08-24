<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BusController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StopController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BusRouteController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\AdminUserController;

// 🔓 Public route to create the FIRST admin only (bootstrap step)
Route::post('/admin/register', [AdminUserController::class, 'storeAdmin']);

// 🔐 Admin Authentication
Route::post('/admin/login', [AuthController::class, 'login']);

// 🔐 Authenticated API
Route::middleware('auth:api')->group(function () {

    // 👤 Authenticated Admin Info
    Route::post('/admin/logout', [AuthController::class, 'logout']);
    Route::get('/admin/me', [AuthController::class, 'me']);

    // 👥 Admin User Management
    Route::prefix('admin')->group(function () {
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::put('/users/{user}', [AdminUserController::class, 'update']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
    });

    // 🚌 Bus Management
    Route::get('/buses', [BusController::class, 'index']);
    Route::get('/buses/{id}', [BusController::class, 'show']);
    Route::post('/buses', [BusController::class, 'store']);
    Route::put('/buses/{id}', [BusController::class, 'update']);
    Route::delete('/buses/{id}', [BusController::class, 'destroy']);

    // 🛣️ Route (BusRoute) Management
    Route::get('/routes', [BusRouteController::class, 'index']);
    Route::get('/routes/{id}', [BusRouteController::class, 'show']);
    Route::post('/routes', [BusRouteController::class, 'store']);
    Route::put('/routes/{id}', [BusRouteController::class, 'update']);
    Route::delete('/routes/{id}', [BusRouteController::class, 'destroy']);

    // 🚏 Stop Management
    Route::get('/stops', [StopController::class, 'index']);
    Route::get('/stops/{id}', [StopController::class, 'show']);
    Route::post('/stops', [StopController::class, 'store']);
    Route::put('/stops/{id}', [StopController::class, 'update']);
    Route::delete('/stops/{id}', [StopController::class, 'destroy']);

    // 📅 Schedule Management
    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::get('/schedules/{schedule}', [ScheduleController::class, 'show']);
    Route::post('/schedules', [ScheduleController::class, 'store']);
    Route::put('/schedules/{schedule}', [ScheduleController::class, 'update']);
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy']);

    // ✈️ Trip Management
    Route::get('/trips', [TripController::class, 'index']);
    Route::get('/trips/{trip}', [TripController::class, 'show']);
    Route::post('/trips', [TripController::class, 'store']);
    Route::put('/trips/{trip}', [TripController::class, 'update']);
    Route::delete('/trips/{trip}', [TripController::class, 'destroy']);

    // 🎟️ Ticket Generation & Viewing
    Route::get('/tickets/my', [TicketController::class, 'myTickets']); // New route for a staff member to view their own tickets
    Route::get('/tickets/admin-tickets', [TicketController::class, 'index']); // New route for admin to view all tickets
    Route::post('/tickets/generate', [TicketController::class, 'generateTicket']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::delete('/tickets/{id}', [TicketController::class, 'destroy']);

    // 💳 Payment Management
    Route::post('/payments/online', [PaymentController::class, 'makeOnlinePayment']);
    Route::post('/payments/staff-payment', [PaymentController::class, 'makeStaffPayment']); // New staff payment endpoint
    Route::get('/payments', [PaymentController::class, 'viewAllPayments']);
    Route::put('/payments/{paymentId}/status', [PaymentController::class, 'updatePaymentStatus']);
});