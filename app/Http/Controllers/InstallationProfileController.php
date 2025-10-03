<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class InstallationProfileController extends Controller
{
    // GET /installation/profile  (view page; use ?edit=1 for edit mode)
    public function index(Request $request)
    {
        $user = Auth::user();
        // you can also pass $edit to the view if you prefer:
        // $edit = $request->boolean('edit');
        return view('installation.profile', compact('user'));
    }

    // PUT /installation/profile  (save changes)
    public function update(Request $request)
    {
        $user = Auth::user();

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

        // return to view mode after saving:
        return redirect()->route('installation.profile')->with('status', 'Profile updated successfully.');
    }
}
