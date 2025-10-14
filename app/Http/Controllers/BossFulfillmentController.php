<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BossFulfillmentController extends Controller
{
    /**
     * Show the Boss Fulfillment page
     * URL: /boss/fulfillment
     */
    public function index()
    {
        // Only return the Blade view (no database yet)
        return view('boss.fulfillment'); // resources/views/boss/fulfillment.blade.php
    }
}
