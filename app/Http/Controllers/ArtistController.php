<?php
namespace App\Http\Controllers;


use App\Models\Order;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    public function dashboard()
    {
        // eager-load the user (artist) relation so name is available
        $orders = Order::with('user')
            ->latest('orderDate')   // or ->orderByDesc('orderDate')
            ->take(50)
            ->get();

        return view('artist.dashboard', compact('orders'));
        // or: return view('artist.dashboard')->with('orders', $orders);
    }
}
