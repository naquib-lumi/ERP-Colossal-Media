<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FurnishingProfileController extends Controller
{
    public function index()
    {
        // Dummy data (frontend only)
        $user = [
            'full_name'      => 'Artist 3',
            'email'          => 'artist3@colossal360.com.my',
            'role'           => 'Artist',
            'contact_number' => '017638298',
            'status'         => 'Active',
            'avatar'         => null,
        ];

        return view('furnishing.profile', compact('user'));
    }
}
