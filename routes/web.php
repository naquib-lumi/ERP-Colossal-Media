<?php

use App\Http\Controllers\SalesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/login', [SalesController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'login']); // Assume AuthController for login logic
Route::middleware(['auth'])->group(function () {
    Route::get('/', [SalesController::class, 'dashboard'])->name('home');
    Route::get('/dashboard', [SalesController::class, 'dashboard'])->name('sales.dashboard');
    Route::get('/lead-management', [SalesController::class, 'leadManagement'])->name('sales.lead-management');
    Route::get('/add-lead', [SalesController::class, 'addLead'])->name('sales.add-lead');
    Route::post('/add-lead', [SalesController::class, 'storeLead']);
    Route::get('/calendar', [SalesController::class, 'calendar'])->name('sales.calendar');
    Route::get('/schedule-meeting', [SalesController::class, 'scheduleMeeting'])->name('sales.schedule-meeting');
    Route::post('/schedule-meeting', [SalesController::class, 'storeMeeting']);
    Route::get('/order', [SalesController::class, 'order'])->name('sales.order');
    Route::get('/job-order-status', [SalesController::class, 'jobOrderStatus'])->name('sales.job-order-status');
});
