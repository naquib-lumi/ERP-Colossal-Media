<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BossDataManagementController extends Controller
{
    /**
     * Show the Boss Data Management page
     * URL: /boss/datamanagement
     */


    public function index(\Illuminate\Http\Request $request)
{
    $activeTab = $request->query('tab', 'dm');
    return view('boss.datamanagement', compact('activeTab'));
}

}
