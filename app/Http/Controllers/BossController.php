<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;

class BossController extends Controller
{
    public function calendar()
{
    $user = Auth::user();
    if (!$user->hasRole('boss')) {
        abort(403, 'Unauthorized');
    }

    $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])
        ->orderBy('name')
        ->get(['id', 'name']);

    return view('boss.calendar', compact('salespeople'));
}
}
