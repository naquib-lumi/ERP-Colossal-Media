<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class PrintingProfileController extends Controller
{
    // GET /dispatch/profile  (view page; add ?edit=1 to show the form)
    public function index(Request $request)
    {
        $user = Auth::user();
        return view('printing.profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // 1) 基本资料校验
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'contact_number' => ['nullable', 'string', 'max:30'],

            // optional password change
            'change_password'  => ['nullable', 'boolean'],
            'current_password' => ['nullable', 'required_with:change_password', 'current_password'],
            'password'         => ['nullable', 'required_with:change_password', 'confirmed', 'min:8'],
        ]);

        $user->name           = $validated['name'];
        $user->email          = $validated['email'];
        $user->contact_number = $validated['contact_number'] ?? null;

        if ($request->boolean('change_password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // back to view mode after saving
        return redirect()->route('printing.profile')->with('status', 'Profile updated successfully.');
    }
}
