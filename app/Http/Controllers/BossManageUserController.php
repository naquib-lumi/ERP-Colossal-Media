<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BossManageUserController extends Controller
{
    /**
     * Show the Boss Manage User page
     * URL: /boss/manageuser
     */
    public function index()
    {
        // Only render the frontend page for now
        return view('boss.manageuser'); // resources/views/boss/manageuser.blade.php
    }
}
