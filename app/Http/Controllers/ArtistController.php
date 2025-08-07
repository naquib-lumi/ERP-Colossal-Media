<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    public function dashboard()
    {
        return view('artist.dashboard'); // You’ll create this view next
    }
}
