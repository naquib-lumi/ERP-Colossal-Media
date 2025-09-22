<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ArtistOrderController;
use App\Http\Controllers\ArtistCalendarController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\FulfillmentController;
use App\Http\Controllers\RedoOrderController;
use App\Http\Controllers\PrintingController;
use App\Http\Controllers\ProductOrderController;

use App\Http\Controllers\ArtistController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test-notification', [NotificationController::class, 'testSelf'])->name('test-notification');
Route::get('/test-allnotif', [NotificationController::class, 'testAll'])->name('test-allnotif');
Route::get('/test-bulknotif', [NotificationController::class, 'testSales'])->name('test-bulknotif');



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
            case 'operations-printing':
                return redirect()->route('printing.dashboard');
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
        Route::get('/sales/orders', [OrderController::class, 'index'])->name('sales.orders');
        Route::get('/sales/calendar', [CalendarController::class, 'index'])->name('sales.calendar');
        Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
        Route::post('/calendar/reminders', [ReminderController::class, 'store'])->name('calendar.reminders.store');
        Route::put('/calendar/reminders/{id}', [ReminderController::class, 'update'])->name('calendar.reminders.update');
        Route::post('/calendar/reminders/{id}/complete', [ReminderController::class, 'complete'])->name('calendar.reminders.complete');
        Route::post('/calendar/meetings', [MeetingController::class, 'storeFromCalendar'])->name('calendar.meetings.store');
        Route::put('/calendar/meetings/{id}', [MeetingController::class, 'updateFromCalendar'])->name('calendar.meetings.update');
        Route::post('/calendar/meetings/{id}/update-status', [MeetingController::class, 'updateCalendarStatus']);
        Route::post('/calendar/reminders/{id}/update-status', [ReminderController::class, 'updateStatus']);
        Route::get('/leads/search', [LeadController::class, 'searchLeads'])->name('leads.search');
        Route::get('/leads/{id}', [LeadController::class, 'getLead'])->name('lead.get');
        
        // Add Order route
        Route::get('/orders/create/{lead_id?}', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{id}/edit', [OrderController::class, 'edit'])->name('orders.edit');
        Route::put('/orders/{id}', [OrderController::class, 'update'])->name('orders.update');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
        Route::delete('/orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');
        Route::post('/orders/get', [OrderController::class, 'getOrders'])->name('orders.get');
        Route::get('/orders/leads/search', [OrderController::class, 'searchLeads'])->name('orders.leads.search');
        Route::get('/orders/leads/{id}', [OrderController::class, 'getLead'])->name('orders.leads.get');
        Route::get('/csv-template', [OrderController::class, 'csvTemplate'])->name('orders.csv_template');

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
        Route::post('/leads/{lead}/meetings', [MeetingController::class, 'storeFromLead'])->name('meetings.store');
        Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
        Route::delete('/calendar/reminders/{id}', [ReminderController::class, 'destroy']);
        Route::delete('/calendar/meetings/{id}', [MeetingController::class, 'destroy']);
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
        // Data Entry assignment (AJAX)
        Route::get('/data-entry/users', [ArtistController::class, 'dataEntryUsers'])->name('dataEntry.users');
        Route::post('/artist/orders/{order}/pass-to-data-entry', [ArtistController::class, 'passToDataEntry'])->name('artist.orders.passToDataEntry');
        Route::get('/data-entry/users', [ArtistController::class, 'dataEntryUsers'])->name('dataEntry.users');
        Route::post('/artist/orders/{order}/pass-to-data-entry', [ArtistController::class, 'passToDataEntry'])->name('artist.orders.passToDataEntry');

        Route::post('/artist/orders/{order}/attachments/upload', [ArtistController::class, 'uploadAttachment'])->name('artist.orders.attachments.upload');
        Route::post('/artist/orders/{order}/attachments/delete', [ArtistController::class, 'deleteAttachment'])->name('artist.orders.attachments.delete');
        Route::delete('/artist/orders/{order}/items/{item}', [ArtistController::class, 'destroyItem'])->name('artist.orders.items.destroy');
        Route::delete('/artist/orders/{order}/delivery/{delivery}', [ArtistController::class, 'deleteDelivery'])->name('artist.orders.delivery.destroy');
        Route::get('/artist/orders/{order}/assign', [ArtistController::class, 'showAssign'])->name('artist.orders.assign.show');
        Route::post('/artist/orders/{order}/assign', [ArtistController::class, 'storeAssign'])->middleware('role:head-artist')->name('artist.orders.assign.store');
        Route::get('/artist/orders/{order}', [ArtistController::class, 'show'])->name('artist.orders.show');
        Route::get('/artist/fulfillment', [FulfillmentController::class, 'index'])->name('artist.fulfillment.index');
        Route::get('/artist/fulfillment/products/{product}', [FulfillmentController::class, 'show'])->name('artist.fulfillment.product.show');
        Route::delete('/artist/orders/{order}/remarks/{remark}', [ArtistController::class, 'destroyRemark'])->name('artist.orders.remarks.destroy');
        // (optional) export PDF button stub
        Route::get('/artist/fulfillment/products/{product}/export', [FulfillmentController::class, 'export'])->name('artist.fulfillment.product.export');
        Route::delete('/artist/orders/{order}/attachments', [ArtistController::class, 'destroyAttachment'])->name('artist.orders.attachments.destroy');
        Route::get('/artist/fulfillment-counts', [FulfillmentController::class, 'fulfillmentCounts'])->name('artist.fulfillmentCounts');
        Route::get('/artist/profile',       [ArtistController::class, 'ProfileShow'])->name('artist.profile.show');
        Route::patch('/artist/profile',     [ArtistController::class, 'ProfileUpdate'])->name('artist.profile.update');
        Route::get('/artist/orders/{order}/redo',  [RedoOrderController::class, 'create'])->name('artist.orders.redo.create');
        Route::post('/artist/orders/{order}/redo', [RedoOrderController::class, 'store'])->name('artist.orders.redo.store');
        Route::post('/artist/calendar', [ArtistCalendarController::class, 'index'])->name('artist.calendar');
        Route::get('/calendar/events', [ArtistCalendarController::class, 'events'])->name('calendar.events');

        Route::pattern('id', '\d+');
        Route::pattern('order', '\d+');
        Route::get('/artist/orders/leads/search', [ArtistOrderController::class, 'searchLeads'])->name('artist.orders.leads.search');        
        Route::get('/artist/orders/leads/{id}', [ArtistOrderController::class, 'getLead'])->name('artist.orders.leads.get');
        Route::get('/artist/orders/create/{lead_id?}', [ArtistOrderController::class, 'create'])->name('artist.orders.create');
        Route::post('/artist/orders', [ArtistOrderController::class, 'store'])->name('artist.orders.store');        
        Route::get('/artist/orders/{id}/edit', [ArtistOrderController::class, 'edit'])->name('artist.orders.edit')->whereNumber('order');;
        Route::put('/artist/orders/{id}', [ArtistOrderController::class, 'update'])->name('artist.orders.update');
        Route::get('/artist/orders/{id}', [ArtistOrderController::class, 'show'])->name('artist.orders.show')->whereNumber('order');;
        Route::delete('/artist/orders/{id}', [ArtistOrderController::class, 'destroy'])->name('artist.orders.destroy');
        Route::post('/artist/orders/get', [ArtistOrderController::class, 'getOrders'])->name('artist.orders.get');
        
        Route::get('/artist/orders/csv-template', [ArtistOrderController::class, 'csvTemplate'])->name('artist.orders.csv_template');
        Route::get('/artist/orders/{order}', [ArtistController::class, 'show'])->name('artist.orders.show')->whereNumber('order');
        Route::get('/artist/orders/{order}/edit', [ArtistController::class, 'edit'])->name('artist.orders.edit')->whereNumber('order');

        // optional AJAX search (also head-only if you want)
        Route::get('/artists/search', [ArtistController::class, 'searchArtists'])
            ->name('artists.search');

    });

    // Printing
    Route::middleware(['web','auth','role:operations-printing'])->group(function () {
        Route::get('/printing/dashboard', [PrintingController::class, 'dashboard'])->name('printing.dashboard');
        // Route::get('/printing/productorder', [ProductOrderController::class, 'productorder'])->name('printing.productorder');
        Route::get('/product-orders', [ProductOrderController::class, 'productorder'])->name('productorders.index');
Route::get('/product-orders/{id}', [ProductOrderController::class, 'show'])->name('productorders.show');
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

require __DIR__ .'/auth.php';