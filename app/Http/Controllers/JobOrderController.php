<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class JobOrderController extends Controller
{
    public function index()
    {
        return view('job.orders');
    }
}