<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\JobOrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\CalendarController;

use App\Http\Controllers\ArtistController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    if (Auth::check()) {
        $user = Auth::user();
        switch ($user->role) {
            case 'salesperson':
                return redirect()->route('sales.dashboard');
            case 'artist':
                return redirect()->route('artist.dashboard');
            case 'head-artist':
                return redirect()->route('artist.dashboard');
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'printing':
            case 'installation':
            case 'delivery':
            case 'furnishing':
                return redirect()->route('operations.tasks');
            case 'boss':
                return redirect()->route('boss.dashboard');
            default:
                return view('dashboard');
        }
    }
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::post('/notifications/{id}/archive', [NotificationController::class, 'archive'])->name('notifications.archive');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:salesperson')->group(function () {
        Route::get('/sales/dashboard', [SalesController::class, 'dashboard'])->name('sales.dashboard');
        Route::get('/sales/orders', [SalesController::class, 'orders'])->name('sales.orders');
        Route::get('/sales/calendar', [CalendarController::class, 'index'])->name('sales.calendar');
        Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
        Route::post('/calendar/reminders', [CalendarController::class, 'storeReminder'])->name('calendar.reminders.store');
        Route::put('/calendar/reminders/{id}', [CalendarController::class, 'updateReminder'])->name('calendar.reminders.update');
        Route::post('/calendar/reminders/{id}/complete', [CalendarController::class, 'completeReminder'])->name('calendar.reminders.complete');
        Route::post('/calendar/meetings', [CalendarController::class, 'storeMeeting'])->name('calendar.meetings.store');
        Route::put('/calendar/meetings/{id}', [CalendarController::class, 'updateMeeting'])->name('calendar.meetings.update');
        Route::get('/leads/search', [CalendarController::class, 'searchLeads'])->name('leads.search');
        Route::get('/leads/{id}', [LeadController::class, 'getLead'])->name('leads.get');
        
        // Add Order route
       Route::get('/sales/orders/{id}/add-order', function ($id) {
            return view('sales.add-order', compact('id'));
        })->name('sales.orders.add');

        // LEAD MANAGEMENT
        Route::get('/sales/leads', [LeadController::class, 'leadManagement'])->name('sales.leads');
        Route::post('/api/leads', [LeadController::class, 'getLeads'])->name('leads.get');
        Route::post('/leads/{id}/update-status', [LeadController::class, 'updateStatus'])->name('leads.update.status');
        Route::post('/leads/{id}/update-opportunity', [LeadController::class, 'updateOpportunity'])->name('leads.update.opportunity');
        Route::post('/leads/{id}/reminders/{reminderId}/confirm', [LeadController::class, 'confirmReminderStatus'])->name('leads.confirm.reminder.status');
        Route::get('/leads/{id}/attachments', [LeadController::class, 'getAttachments'])->name('leads.attachments');
        Route::delete('/leads/{id}/attachments/{attachment}', [LeadController::class, 'deleteAttachment'])->name('leads.attachments.delete');
        Route::post('/leads/{id}/add-attachment', [LeadController::class, 'addAttachment'])->name('leads.add.attachment');
        Route::get('/leads/{id}/edit', [LeadController::class, 'edit'])->name('leads.edit');
        Route::put('/leads/{id}/update', [LeadController::class, 'update'])->name('leads.update');
        Route::get('/leads/{id}/view', [LeadController::class, 'show'])->name('leads.show');
        Route::post('/leads/{id}/add-reminder', [LeadController::class, 'addReminder'])->name('leads.add.reminder');
        Route::post('/leads/{id}/add-note', [LeadController::class, 'addNote'])->name('leads.add.note');
        Route::post('/leads/{id}/update-salesperson', [LeadController::class, 'updateSalesperson'])->name('leads.update.salesperson');
        Route::delete('/api/leads/{id}', [LeadController::class, 'destroy'])->name('leads.destroy');
        Route::get('/sales/add-lead', [LeadController::class, 'create'])->name('leads.create');
        Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
        Route::post('/leads/{lead}/meetings', [MeetingController::class, 'store'])->name('meetings.store');
    });

    Route::middleware('role:artist')->group(function () {
        Route::get('/job/orders', [JobOrderController::class, 'index'])->name('job.orders');
        Route::post('/leads/{id}/confirm-reminder', [LeadController::class, 'confirmReminder'])->name('leads.confirm.reminder');
        Route::get('/leads/{id}/attachments', [LeadController::class, 'getAttachments'])->name('leads.attachments');
        Route::get('/leads/{id}/edit', [LeadController::class, 'edit'])->name('leads.edit');
        Route::get('/leads/{id}', [LeadController::class, 'show'])->name('leads.show');
        Route::post('/leads/{id}/add-reminder', [LeadController::class, 'addReminder'])->name('leads.add.reminder');
        Route::post('/leads/{id}/add-note', [LeadController::class, 'addNote'])->name('leads.add.note');
        Route::delete('/api/leads/{id}', [LeadController::class, 'destroy']);
        Route::get('/sales/add-lead', [LeadController::class, 'create'])->name('leads.create');
        Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    });

    Route::get('/test-route', function () {
        return 'Route is working';
    });

    // Artist 
    Route::middleware(['web','auth','role:artist,head-artist'])->group(function () {
        Route::get('/artist/dashboard', [ArtistController::class, 'dashboard'])->name('artist.dashboard');
        Route::get('/artist/meetingStatusCounts', [ArtistController::class, 'meetingStatusCounts'])->name('artist.meetingStatusCounts');
        Route::get('/artist/orders', [ArtistController::class, 'orders'])->name('artist.orders');
        Route::get('/artist/orders/{order}/edit', [ArtistController::class, 'edit'])->name('artist.orders.edit');
        Route::put('/artist/orders/{order}', [ArtistController::class, 'update'])->name('artist.orders.update');
        Route::post('/artist/orders/{order}/attachments/upload', [ArtistController::class, 'uploadAttachment'])->name('artist.orders.attachments.upload');
        Route::post('/artist/orders/{order}/attachments/delete', [ArtistController::class, 'deleteAttachment'])->name('artist.orders.attachments.delete');

        // Actions ONLY a head-artist can do
        Route::post('/artist/orders/{order}/assign', [ArtistController::class, 'assign'])
            ->middleware('role:head-artist')
            ->name('artist.orders.assign');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');
        Route::get('/admin/reports', [AdminController::class, 'reports'])->name('admin.reports');
    });

    Route::middleware('role:printing,installation,delivery,furnishing')->group(function () {
        Route::get('/operations/tasks', [OperationsController::class, 'tasks'])->name('operations.tasks');
    });

    Route::middleware('role:boss')->group(function () {
        Route::get('/boss/dashboard', [AdminController::class, 'dashboard'])->name('boss.dashboard');
        Route::get('/boss/reports', [AdminController::class, 'reports'])->name('boss.reports');
    });
});

require __DIR__.'/auth.php';