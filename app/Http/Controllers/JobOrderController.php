<?php

namespace App\Http\Controllers;

use App\Models\Order;

class JobOrderController extends Controller
{
    public function index()
    {
        // Pull latest orders with user (artist) and lead eager-loaded
        $orders = Order::with(['user:id,name','lead:id,name'])
            ->latest('orderDate')
            ->take(200) // safety cap for DT client-side
            ->get();

        return view('artist.dashboard', compact('orders'));
    }
}
