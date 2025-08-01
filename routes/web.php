<?php
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\JobOrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OperationsController;
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
                return redirect()->route('job.orders');
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
        Route::get('/sales/leads', [SalesController::class, 'leads'])->name('sales.leads');
        Route::get('/sales/calendar', [SalesController::class, 'calendar'])->name('sales.calendar');
        Route::get('/sales/orders', [SalesController::class, 'orders'])->name('sales.orders');
    });

    Route::middleware('role:artist')->group(function () {
        Route::get('/job/orders', [JobOrderController::class, 'index'])->name('job.orders');
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