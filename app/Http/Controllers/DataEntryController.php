<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Meeting;
use App\Models\Material;
use App\Models\ProductRemark;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\LeadAttachment;
use App\Models\DeliveryBreakdown;
use App\Models\Specification;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class DataEntryController extends Controller
{


public function orders(Request $request)
{
    $user = auth()->user();

    $base = Order::query()
        ->where(function ($q) { $q->whereNull('status')->orWhere('status', 0); })
        ->when($user && $user->role !== 'boss', fn ($q) => $q->where('data_entry_id', $user->id));

    $query = clone $base;

    if ($s = trim($request->query('q', ''))) {
        $query->where(function ($q) use ($s) {
            $q->where('orderTitle', 'like', "%{$s}%")
              ->orWhere('companyName', 'like', "%{$s}%")
              ->orWhere('leadName', 'like', "%{$s}%");
        });
    }

    // 🔧 ensure we fetch all columns + the computed one
    $query->select('orders.*')->addSelect([
        'ui_status' => DB::raw("CASE WHEN submit=1 AND pending=1 THEN 'pending' ELSE orderStatus END"),
    ]);

    $orders = $query->with(['artist:id,name', 'salesperson:id,name'])
                    ->orderByDesc('orderDate')
                    ->paginate(50)
                    ->withQueryString();

    // metrics (unchanged)
    $statsBase = (clone $base);
    $metrics = [
        'total'       => (clone $statsBase)->count(),
        'pending'     => (clone $statsBase)->where('submit', 1)->where('pending', 1)->count(),
        'to_assign'   => (clone $statsBase)->where('orderStatus', 'to_assign')->count(),
        'assigned'    => (clone $statsBase)->where('orderStatus', 'assigned')->count(),
        'in_progress' => (clone $statsBase)->where('orderStatus', 'in_progress')->count(),
        'completed'   => (clone $statsBase)->where('orderStatus', 'completed')
                                           ->whereRaw('NOT (submit=1 AND pending=1)')->count(),
        'rejected'    => (clone $statsBase)->where('orderStatus', 'rejected')->count(),
    ];

    $statusRaw = '';

    if ($request->ajax()) {
        return view('data-entry.partials.orders-table', ['orders' => $orders])->render();
    }

    return view('data-entry.orders', compact('orders', 'metrics', 'statusRaw'));
}


    public function show(Order $order)
    {
        $user = auth()->user();
        if ($user->role !== 'boss' && $order->data_entry_id !== $user->id) {
            abort(403);
        }

        // (Optional) clear pending when opened
        Order::where('id', $order->id)->where('submit', 1)->where('pending', 1)->update(['pending' => 0]);
        $order->load(['artist:id,name', 'salesperson:id,name']);

        return view('data-entry.order.show', compact('order'));
    }

    public function edit(Order $order)
    {
        $user = auth()->user();
        if ($user->role !== 'boss' && $order->data_entry_id !== $user->id) {
            abort(403);
        }

        $order->load(['artist:id,name', 'salesperson:id,name', /* add relations you need */]);

        return view('data-entry.order.edit', compact('order'));
    }
}
