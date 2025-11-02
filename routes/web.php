<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\MaterialsController;
use App\Http\Controllers\CalendarController;


use App\Http\Controllers\OperationsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ArtistOrderController;
use App\Http\Controllers\ArtistCalendarController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\FulfillmentController;
use App\Http\Controllers\RedoOrderController;
use App\Http\Controllers\PrintingController;
use App\Http\Controllers\ProductOrderController;

use App\Http\Controllers\ArtistController;
use App\Http\Controllers\LogisticOrderHistoryController;
use App\Http\Controllers\PrintingHistoryController;
use App\Http\Controllers\PrintingProfileController;
// web.php 顶部
use App\Http\Controllers\PrintingProductOrderController; // ← 没有子命名空间时用这行



use App\Http\Controllers\FurnishingController;
use App\Http\Controllers\FurnishingHistoryController;
use App\Http\Controllers\FurnishingProductOrderController;

use App\Http\Controllers\InstallationController;
use App\Http\Controllers\InstallationHistoryController;
use App\Http\Controllers\InstallationProductOrderController;
use App\Http\Controllers\InstallationProfileController;

use App\Http\Controllers\DispatchControlController;
use App\Http\Controllers\DispatchControlHistoryController;
use App\Http\Controllers\DispatchControlProductOrderController;
use App\Http\Controllers\DispatchControlProfileController;
use App\Http\Controllers\DispatchControlJobOrderTableController;

use App\Http\Controllers\DataEntryController;

use App\Http\Controllers\BossDashboardController;
use App\Http\Controllers\BossFulfillmentController;
use App\Http\Controllers\BossReportController;
use App\Http\Controllers\BossManageUserController;
use App\Http\Controllers\BossDataManagementController;



use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;



// Route::prefix('logistic')->name('logistic.')->group(function () {
//     // Order History (frontend only)
//     Route::get('/order-history', [LogisticOrderHistoryController::class, 'index'])
//         ->name('orderHistory');
// });

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
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
             case 'head-salesperson':
                return redirect()->route('sales.dashboard');
            case 'artist':
                return redirect()->route('artist.dashboard');
            case 'head-artist':
                return redirect()->route('artist.dashboard');
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'operations-printing':
                return redirect()->route('printing.dashboard');
            case 'operations-delivery-installation':
                return redirect()->route('installation.dashboard');
            case 'operations-dispatch-control':
                return redirect()->route('dispatchcontrol.dashboard');
            case 'operations-furnishing':
                return redirect()->route('furnishing.dashboard');
            case 'boss':
                return redirect()->route('boss.dashboard');
            case 'data-entry':
                return redirect()->route('data-entry.orders');
            default:
                return view('dashboard');
        }
    }
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.markAsRead');

        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.markAllAsRead');

        Route::post('/notifications/{id}/archive', [NotificationController::class, 'archive'])
        ->name('notifications.archive');

        Route::get('/notifications/count', [NotificationController::class, 'count'])
            ->name('notifications.count');

        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');

        // optional
        Route::get('/notifications/latest-unread', [NotificationController::class, 'latestUnread'])
            ->name('notifications.latestUnread');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:salesperson|head-salesperson')->group(function () { 

        Route::get('/notifications/reminders/{reminder}/complete', [ReminderController::class, 'completeFromNotification'])->name('reminders.complete.notification');
        Route::get('/sales/profile', [SalesController::class, 'ProfileShow'])->name('sales.profile.show');
        Route::patch('/sales/profile', [SalesController::class, 'ProfileUpdate'])->name('sales.profile.update');
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
       Route::get('/sales/orders/export', [OrderController::class, 'exportCsv'])->name('orders.export');
        
        Route::delete('/orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');
        Route::post('/orders/get', [OrderController::class, 'getOrders'])->name('orders.get');
        Route::get('/orders/leads/search', [OrderController::class, 'searchLeads'])->name('orders.leads.search');
        Route::get('/orders/leads/{id}', [OrderController::class, 'getLead'])->name('orders.leads.get');
        Route::get('/csv-template', [OrderController::class, 'csvTemplate'])->name('orders.csv_template');

        // LEAD MANAGEMENT
        Route::get('/sales/leads', [LeadController::class, 'leadManagement'])->name('sales.leads');
        Route::get('/sales/leads/export', [LeadController::class, 'exportCsv'])->name('leads.export-csv');
        

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
        Route::delete('/leads/{id}/notes/{note}', [LeadController::class, 'deleteNote'])->name('leads.notes.delete');
        Route::delete('/leads/{id}/notes/{note}/attachments/{attachment}', [LeadController::class, 'deleteNoteAttachment'])->name('leads.note.attachments.delete');
        Route::post('/leads/{id}/update-salesperson', [LeadController::class, 'updateSalesperson'])->name('leads.update.salesperson');
        Route::delete('/api/leads/{id}', [LeadController::class, 'destroy'])->name('leads.destroy');
        Route::get('/sales/add-lead', [LeadController::class, 'create'])->name('leads.create');
        Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
        Route::post('/leads/{lead}/meetings', [MeetingController::class, 'storeFromLead'])->name('meetings.store');
        Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
        Route::delete('/calendar/reminders/{id}', [ReminderController::class, 'destroy']);
        Route::delete('/calendar/meetings/{id}', [MeetingController::class, 'destroy']);

        Route::get('/meetings/{id}', [MeetingController::class, 'show'])->name('meetings.show');  // Add for edit fetch
        Route::post('/meetings/{id}/status', [MeetingController::class, 'updateStatus'])->name('meetings.update.status');
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

        Route::post('/artist/orders/{order}/attachments/upload', [ArtistController::class, 'uploadAttachment'])->name('artist.orders.attachments.upload');
        Route::post('/artist/orders/{order}/attachments/delete', [ArtistController::class, 'deleteAttachment'])->name('artist.orders.attachments.delete');
        Route::delete('/artist/orders/{order}/items/{item}', [ArtistController::class, 'destroyItem'])->name('artist.orders.items.destroy');
        Route::delete('/artist/orders/{order}/delivery/{delivery}', [ArtistController::class, 'deleteDelivery'])->name('artist.orders.delivery.destroy');
        Route::get('/artist/orders/{order}/assign', [ArtistController::class, 'showAssign'])->name('artist.orders.assign.show');
        Route::post('/artist/orders/{order}/assign', [ArtistController::class, 'storeAssign'])->middleware('role:head-artist')->name('artist.orders.assign.store');
        Route::get('/artist/orders/{order}', [ArtistController::class, 'show'])->name('artist.orders.show');
        Route::post('/artist/orders/{order}/products', [ArtistOrderController::class, 'storeProduct'])->name('artist.orders.products.store');
        Route::delete('/artist/orders/{order}/products/{product}', [ArtistController::class, 'destroyProduct'])->name('artist.orders.products.destroy');

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
        Route::get('/artist/calendar', [ArtistCalendarController::class, 'index'])->name('artist.calendar');
        Route::get('/artist/calendar/events', [ArtistCalendarController::class, 'events'])->name('artist.calendar.events');

        Route::pattern('id', '\d+');
        Route::pattern('order', '\d+');
        Route::get('/artist/orders/leads/search', [ArtistOrderController::class, 'searchLeads'])->name('artist.orders.leads.search');        
        Route::get('/artist/orders/leads/{id}', [ArtistOrderController::class, 'getLead'])->name('artist.orders.leads.get');
        Route::get('/artist/orders/create/{lead_id?}', [ArtistOrderController::class, 'create'])->name('artist.orders.create');
        Route::post('/artist/orders', [ArtistOrderController::class, 'store'])->name('artist.orders.store');        
        Route::get('/artist/orders/{id}/edit', [ArtistOrderController::class, 'edit'])->name('artist.orders.edit')->whereNumber('order');
        Route::put('/artist/orders/{id}', [ArtistOrderController::class, 'update'])->name('artist.orders.update');
        Route::get('/artist/orders/{id}', [ArtistOrderController::class, 'show'])->name('artist.orders.show')->whereNumber('order');
        Route::delete('/artist/orders/{id}', [ArtistOrderController::class, 'destroy'])->name('artist.orders.destroy');
        Route::post('/artist/orders/get', [ArtistOrderController::class, 'getOrders'])->name('artist.orders.get');
        
        Route::get('/artist/orders/csv-template', [ArtistOrderController::class, 'csvTemplate'])->name('artist.orders.csv_template');
        Route::get('/artist/orders/{order}', [ArtistController::class, 'show'])->name('artist.orders.shows')->whereNumber('order');
        Route::get('/artist/orders/{order}/edit', [ArtistController::class, 'edit'])->name('artist.orders.edit')->whereNumber('order');

        // AJAX search for artists (head-artist assigning)
        Route::get('/artist/orders/assignees/search', [ArtistOrderController::class, 'searchArtists'])->name('artist.orders.assignees.search');
        Route::post('/artist/orders/{order}/edit', [ArtistOrderController::class, 'assign'])->name('artist.orders.assigns');

        // optional AJAX search (also head-only if you want)
        Route::get('/artists/search', [ArtistController::class, 'searchArtists'])
            ->name('artists.search');

    });

    Route::middleware(['web','auth','role:data-entry'])->group(function () {
        Route::get('/data-entry/dashboard', [DataEntryController::class, 'dashboard'])->name('data-entry.dashboard');
        Route::get('/data-entry/orders', [DataEntryController::class, 'orders'])->name('data-entry.orders');
        Route::get('/data-entry/orders/{order}',        [DataEntryController::class, 'show'])->name('data-entry.orders.show');
        Route::get('/data-entry/orders/{order}/edit',   [DataEntryController::class, 'edit'])->name('data-entry.orders.edit');

        Route::put('/data-entry/orders/{order}', [DataEntryController::class, 'update'])->name('data-entry.orders.update');
        
        // If your Blade still uses these actions:
        Route::delete('/data-entry/orders/{order}/remarks/{remark}', [DataEntryController::class, 'destroyRemark'])->name('data-entry.orders.remarks.destroy');
        Route::delete('/data-entry/orders/{order}/items/{item}', [DataEntryController::class, 'destroyItem'])->name('data-entry.orders.items.destroy');
        Route::delete('/data-entry/orders/{order}/delivery/{delivery}', [DataEntryController::class, 'deleteDelivery'])->name('data-entry.orders.delivery.destroy');
        Route::delete('/data-entry/orders/{order}/attachments', [DataEntryController::class, 'destroyAttachment'])->name('data-entry.orders.attachments.destroy');
        Route::patch('/data-entry/orders/{order}/begin', [DataEntryController::class, 'begin'])->name('data-entry.orders.begin');
    });

    // Printing
        Route::middleware(['web','auth','role:operations-printing'])->group(function () {
            Route::prefix('printing')->name('printing.')->group(function () {
            Route::get('/dashboard', [PrintingController::class, 'dashboard'])->name('dashboard');
            Route::get('/jobs/{product}', [PrintingProductOrderController::class, 'show'])->name('orders.show');
            Route::post('/jobs/{product}/accept', [PrintingProductOrderController::class, 'accept'])->name('orders.accept');
            Route::post('/jobs/{product}/reject', [PrintingProductOrderController::class, 'reject'])->name('orders.reject');
            Route::post('/job/{product}/save', [PrintingProductOrderController::class, 'save'])->name('jobs.save');
            Route::patch('/jobs/{productId}/complete', [PrintingController::class, 'markPrinted'])->name('jobs.complete');
            Route::get('/history', [PrintingHistoryController::class, 'index'])->name('history');
            Route::get('/profile', [PrintingProfileController::class, 'index'])->name('profile');
            Route::put('/profile', [PrintingProfileController::class, 'update'])->name('profile.update');
            Route::get('/report/{productId}', [PrintingController::class, 'reportForm'])->name('report');
            Route::post('/report/{productId}', [PrintingController::class, 'reportSubmit'])->name('report.submit');
            Route::get('/product-orders', [ProductOrderController::class, 'productorder'])->name('productorders.index');
            Route::get('/product-orders/{id}', [ProductOrderController::class, 'show'])->name('productorders.show');
            Route::post('/update-printers', [PrintingController::class, 'updatePrinters'])->name('update.printers');
            Route::get('/history/{product}', [PrintingHistoryController::class, 'show'])->name('history.show');

        });
    });

    // Furnishing
    Route::middleware(['web','auth','role:operations-furnishing'])->group(function () {
        Route::get('/furnishing/dashboard', [FurnishingController::class, 'dashboard'])->name('furnishing.dashboard');
        Route::patch('/furnishing/jobs/{product}/complete', [FurnishingController::class, 'markComplete'])->name('furnishing.jobs.complete');
        Route::get('/furnishing/jobs/{product}', [FurnishingProductOrderController::class, 'show'])->name('furnishing.orders.show');
        Route::post('/furnishing/jobs/{product}/accept', [FurnishingProductOrderController::class, 'accept'])->name('furnishing.orders.accept');
        Route::post('/furnishing/jobs/{product}/reject', [FurnishingProductOrderController::class, 'reject'])->name('furnishing.orders.reject');
        Route::get('/furnishing/product-order', [FurnishingProductOrderController::class, 'productorder'])->name('furnishing.product-order');
        Route::get('/furnishing/history', [FurnishingHistoryController::class, 'index'])->name('furnishing.history');
        Route::get('/furnishing/profile', [\App\Http\Controllers\FurnishingProfileController::class, 'index'])->name('furnishing.profile');
        Route::put('/furnishing/profile', [\App\Http\Controllers\FurnishingProfileController::class, 'update'])->name('furnishing.profile.update');
        Route::patch('/furnishing/jobs/{productId}/complete', [FurnishingController::class, 'markComplete'])->name('furnishing.jobs.complete');
        Route::get('/furnishing/job/{product}', [\App\Http\Controllers\FurnishingProductOrderController::class, 'show'])->name('furnishing.job.show');
        Route::post('/furnishing/job/{product}/accept', [\App\Http\Controllers\FurnishingProductOrderController::class, 'accept'])->name('furnishing.orders.accept');
        Route::post('/furnishing/job/{product}/reject', [\App\Http\Controllers\FurnishingProductOrderController::class, 'reject'])->name('furnishing.orders.reject');
        Route::post('/furnishing/job/{product}/save', [\App\Http\Controllers\FurnishingProductOrderController::class, 'save'])->name('furnishing.jobs.save');
        Route::get('/furnishing/history/{product}', [FurnishingHistoryController::class, 'show'])->name('furnishing.history.show');
    });

    // Delivery and installation
    Route::middleware(['web','auth','role:operations-delivery-installation'])->group(function () {
        Route::get('/installation/dashboard', [InstallationController::class, 'dashboard'])->name('installation.dashboard');
        
        Route::get('/installation/job/{product}', [\App\Http\Controllers\InstallationProductOrderController::class, 'show'])->name('installation.job.show');
        Route::post('/installation/job/{product}/accept', [\App\Http\Controllers\InstallationProductOrderController::class, 'accept'])->name('installation.orders.accept');
        Route::post('/installation/job/{product}/reject', [\App\Http\Controllers\InstallationProductOrderController::class, 'reject'])->name('installation.orders.reject');
        Route::post('/installation/job/{product}/save', [\App\Http\Controllers\InstallationProductOrderController::class, 'save'])->name('installation.jobs.save');

        Route::get('/installation/history', [InstallationHistoryController::class, 'index'])->name('installation.history');
        Route::get('/installation/history/{product}', [InstallationHistoryController::class, 'show'])->name('installation.history.show');
        Route::get('/installation/history/{product}/proofs', [\App\Http\Controllers\InstallationHistoryController::class, 'proofs'])->name('installation.history.proofs');
        
        Route::get('/installation/profile', [InstallationProfileController::class, 'index'])->name('installation.profile');
        Route::put('/installation/profile', [InstallationProfileController::class, 'update'])->name('installation.profile.update');
        Route::get('/installation/calendar', [\App\Http\Controllers\InstallationCalendarController::class, 'index'])->name('installation.calendar');
        Route::get('/installation/calendar/events', [\App\Http\Controllers\InstallationCalendarController::class, 'events'])->name('installation.calendar.events');

        Route::patch('/installation/jobs/{product}/complete', [InstallationController::class, 'completeWithProof'])->name('installation.jobs.complete');
    });


    Route::middleware(['web','auth','role:operations-dispatch-control'])->group(function () {
        Route::get('/dispatchcontrol/dashboard', [DispatchControlController::class, 'dashboard'])->name('dispatchcontrol.dashboard');
        Route::get('/dispatchcontrol/product-order', [DispatchControlProductOrderController::class, 'productorder'])->name('dispatchcontrol.product-order');

        Route::get('/dispatchcontrol/job/{product}', [DispatchControlProductOrderController::class, 'show'])->name('dispatchcontrol.job.show');
        Route::post('/dispatchcontrol/job/{product}/accept', [DispatchControlProductOrderController::class, 'accept'])->name('dispatchcontrol.orders.accept');
        Route::post('/dispatchcontrol/job/{product}/reject', [DispatchControlProductOrderController::class, 'reject'])->name('dispatchcontrol.orders.reject');
        Route::post('/dispatchcontrol/job/{product}/save', [DispatchControlProductOrderController::class, 'save'])->name('dispatchcontrol.jobs.save');

        Route::get('/dispatchcontrol/history', [DispatchControlHistoryController::class, 'index'])->name('dispatchcontrol.history');
        Route::get('/dispatchcontrol/history/{product}', [DispatchControlHistoryController::class, 'show'])->name('dispatchcontrol.history.show');
        Route::get('/dispatchcontrol/history/{product}/proofs', [DispatchControlHistoryController::class, 'proofs'])->name('dispatchcontrol.history.proofs');

        Route::get('/dispatchcontrol/job-order', [DispatchControlController::class, 'index'])->name('dispatchcontrol.job-order');
        Route::get('/dispatchcontrol/user', [DispatchControlProfileController::class, 'index'])->name('dispatchcontrol.user');
        Route::put('/dispatchcontrol/profile', [DispatchControlProfileController::class, 'update'])->name('dispatchcontrol.user.update');

        Route::patch('/dispatchcontrol/jobs/{product}/complete', [DispatchControlController::class, 'completeWithProof'])->name('dispatchcontrol.jobs.complete');
    });



Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/leads-monthly', [AdminController::class, 'leadsMonthly'])->name('admin.leadsMonthly');
    Route::get('/admin/leads-breakdown', [AdminController::class, 'leadsBreakdown'])->name('admin.leadsBreakdown');
    Route::get('/admin/fulfillment-counts', [AdminController::class, 'fulfillmentCounts'])->name('admin.fulfillmentCounts');
    Route::get('/admin/fulfillment', [AdminController::class, 'fulfillment'])->name('admin.fulfillment');
    Route::get('/admin/fulfillment/{id}', [AdminController::class, 'fulfillmentShow'])->name('admin.fulfillment.show');
    Route::get('/admin/fulfillment/{id}/edit', [AdminController::class, 'fulfillmentEdit'])->name('admin.fulfillment.edit');
    Route::get('/admin/manageuser', [AdminController::class, 'manageUser'])->name('admin.manageuser');
    Route::get('/admin/user', [AdminController::class, 'user'])->name('admin.user');
    Route::post('/admin/user', [AdminController::class, 'storeUser'])->name('admin.user.store');
    Route::put('/admin/user/{user}', [AdminController::class, 'updateUser'])->name('admin.user.update');
    Route::patch('/admin/user/{user}/disable', [AdminController::class, 'disableUser'])->name('admin.user.disable');
    Route::get('/admin/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::get('/admin/orders/{id}', [AdminController::class, 'showOrder'])->name('admin.orders.show');
    Route::post('/admin/orders/data', [AdminController::class, 'getOrders'])->name('admin.orders.data');

    Route::get('/admin/calendar', [AdminController::class, 'calendar'])->name('admin.calendar');
    Route::get('/calendar/admin-events', [CalendarController::class, 'events'])->name('calendar.events');
    Route::get('/calendar/order-events', [CalendarController::class, 'orderEvents'])->name('calendar.order-events');
    Route::get('/admin/reports', [AdminReportController::class, 'index'])->name('admin.reports');
    Route::get('/admin/reportstest', [AdminReportController::class, 'reportstest'])->name('admin.reportstest');

    Route::post('/admin/reports/sales-kpis', [AdminReportController::class, 'salesKpis'])->name('admin.report.sales-kpis');
    Route::post('/admin/reports/sales-monthly', [AdminReportController::class, 'salesMonthlyPerformance'])->name('admin.report.sales-monthly');
    Route::post('/admin/reports/sales-outcomes', [AdminReportController::class, 'salesOutcomes'])->name('admin.report.sales-outcomes');
    Route::post('/admin/reports/order-fulfillment', [AdminReportController::class, 'orderFulfillment'])->name('admin.report.order-fulfillment');
    Route::get('/admin/reports/export-sales', [AdminReportController::class, 'exportSales'])->name('admin.report.export-sales');
    Route::post('/admin/reports/export-orders', [AdminReportController::class, 'exportOrders'])->name('admin.report.export-orders');
  
   Route::get('/admin/costing-data', [MaterialsController::class, 'index'])->name('admin.costing-data');
    Route::post('/admin/material-types', [MaterialsController::class, 'storeType'])->name('admin.material-types.store');
    Route::put('/admin/material-types/{id}', [MaterialsController::class, 'updateType'])->name('admin.material-types.update');
    Route::delete('/admin/material-types/{id}', [MaterialsController::class, 'destroyType'])->name('admin.material-types.destroy');
    Route::post('/admin/units', [MaterialsController::class, 'storeUnit'])->name('admin.units.store');
    Route::put('/admin/units/{id}', [MaterialsController::class, 'updateUnit'])->name('admin.units.update');
    Route::delete('/admin/units/{id}', [MaterialsController::class, 'destroyUnit'])->name('admin.units.destroy');
    Route::post('/admin/materials', [MaterialsController::class, 'store'])->name('admin.materials.store');
    Route::put('/admin/materials/{id}', [MaterialsController::class, 'update'])->name('admin.materials.update');
    Route::delete('/admin/materials/{id}', [MaterialsController::class, 'destroy'])->name('admin.materials.destroy');

    // Profile
    Route::get('/admin/profile', [AdminController::class, 'ProfileShow'])->name('admin.profile.show');
    Route::patch('/admin/profile', [AdminController::class, 'ProfileUpdate'])->name('admin.profile.update');
});

    Route::get('/admin/dispatch-control', [AdminController::class, 'dispatchControl'])->name('admin.dispatch');
    Route::get('/admin/dispatch-control/export', [AdminController::class, 'dispatchExport'])->name('admin.dispatch.export');
Route::get('/admin/installation', [AdminController::class, 'installation'])->name('admin.installation');
Route::get('/admin/installation/export', [AdminController::class, 'installationExport'])->name('admin.installation.export');
Route::post('/admin/installation/{order}/permit', [AdminController::class, 'installationPermitUpload'])->name('admin.installation.permit.upload');
Route::post('/admin/installation/permit', [AdminController::class,'installationPermitStore'])->name('admin.installation.permit.store');
Route::get('/admin/installation/permit/{permit}/download', [AdminController::class,'installationPermitDownload'])->name('admin.installation.permit.download');
Route::delete('/admin/installation/permit/{permit}', [AdminController::class,'installationPermitDestroy'])->name('admin.installation.permit.destroy');





    Route::middleware('role:printing,installation,delivery,furnishing')->group(function () {
        Route::get('/operations/tasks', [OperationsController::class, 'tasks'])->name('operations.tasks');
    });

    Route::middleware('role:boss')->group(function () {
        Route::get('/boss/dashboard', [BossDashboardController::class, 'index'])->name('boss.dashboard');
        Route::get('/boss/reports', [BossReportController::class, 'index'])->name('boss.reports');
        Route::get('/boss/reports/sales/export', [BossReportController::class, 'exportSales'])->name('boss.reports.sales.export');

        Route::get('/boss/fulfillment', [BossFulfillmentController::class, 'index'])->name('boss.fulfillment');
        Route::get('/boss/manageuser', [BossManageUserController::class, 'index'])->name('boss.manageuser');
        Route::get('/boss/datamanagement', [BossDataManagementController::class, 'index'])->name('boss.datamanagement');
        
        // Users
        Route::get('/boss/manageuser', [BossManageUserController::class, 'manageUser'])->name('boss.manageuser');
        Route::get('/boss/user', [BossManageUserController::class, 'user'])->name('boss.user'); // DataTables JSON（可选）
        Route::post('/boss/user', [BossManageUserController::class, 'storeUser'])->name('boss.user.store');
        Route::put('/boss/user/{user}', [BossManageUserController::class, 'updateUser'])->name('boss.user.update');
        Route::patch('/boss/user/{user}/disable', [BossManageUserController::class, 'disableUser'])->name('boss.user.disable');

        // update profile
        Route::get('/boss/profile',       [BossDashboardController::class, 'ProfileShow'])->name('boss.profile.show');
        Route::patch('/boss/profile',     [BossDashboardController::class, 'ProfileUpdate'])->name('boss.profile.update');
    });


 
});

require __DIR__ .'/auth.php';