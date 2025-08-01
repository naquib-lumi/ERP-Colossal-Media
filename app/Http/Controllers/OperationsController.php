<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OperationsController extends Controller
{
    public function tasks()
    {
        return view('operations.tasks');
    }
}