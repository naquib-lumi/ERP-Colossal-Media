<?php
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\JobOrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OperationsController;
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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:salesperson')->group(function () {
        Route::get('/sales/dashboard', [SalesController::class, 'dashboard'])->name('sales.dashboard');
        Route::get('/sales/calendar', [SalesController::class, 'calendar'])->name('sales.calendar');
        Route::get('/sales/orders', [SalesController::class, 'orders'])->name('sales.orders');

        // LEAD MANAGEMENT
  Route::get('/sales/leads', [LeadController::class, 'leadManagement'])->name('sales.leads');
    Route::post('/api/leads', [LeadController::class, 'getLeads'])->name('leads.get');
    Route::post('/leads/{id}/update-status', [LeadController::class, 'updateStatus'])->name('leads.update.status');
    Route::post('/leads/{id}/update-opportunity', [LeadController::class, 'updateOpportunity'])->name('leads.update.opportunity');
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
    Route::middleware(['web','auth','role:artist'])->group(function () {
        Route::get('/artist/dashboard', [ArtistController::class, 'dashboard'])->name('artist.dashboard');
        Route::get('/artist/meetingStatusCounts', [ArtistController::class, 'meetingStatusCounts'])->name('artist.meetingStatusCounts');

        // NEW
        Route::get('/artist/orders', [ArtistController::class, 'orders'])->name('artist.orders');
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