<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BossReportController extends Controller
{
    /**
     * Show the Boss Report page
     * URL: /boss/reports
     */
    public function index()
    {
        // For now, just display the frontend Blade file (no database yet)
        return view('boss.reports'); // resources/views/boss/report.blade.php
    }
}
