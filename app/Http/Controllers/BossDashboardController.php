<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BossDashboardController extends Controller
{
    /**
     * Show the Boss Dashboard page
     * URL: /boss/dashboard
     */
    public function index()
    {
        // For now, only render the frontend page.
        return view('boss.dashboard'); // points to resources/views/dashboard.blade.php
    }
}
