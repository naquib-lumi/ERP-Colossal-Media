<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Lead;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class BossManageUserController extends Controller
{
    private function allowedRoles(): array
    {
        return [
            'admin',
            'boss',
            'salesperson',
            'head-artist',
            'artist',
            'operations-printing',
            'operations-furnishing',
            'operations-dispatch-control',
            'operations-delivery-installation',
            'data-entry',
            'installation', // 如果你在前端下拉里用到了
            'head-salesperson',
        ];
    }
    
    public function manageUser(Request $request)
    {
        if (!Auth::user()->hasRole('boss')) abort(403, 'Unauthorized');

        $q      = trim((string)$request->query('q', ''));
        $role   = $request->query('role', 'all');
        $status = $request->query('status', 'all');

        $users = User::query()
            ->when($q !== '', function ($qbuilder) use ($q) {
                $qbuilder->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('contact_number', 'like', "%{$q}%");
                });
            })
            ->when($role !== 'all', fn($qb) => $qb->where('role', $role))
            ->when($status !== 'all', fn($qb) => $qb->where('status', strtolower($status)))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('boss.manageuser', [
            'users'       => $users,
            'roleOptions' => $this->allowedRoles(),
        ]);
    }

    public function user(Request $request)
    {
        if (!Auth::user()->hasRole('boss')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $q      = trim((string)$request->get('q',''));
        $role   = $request->get('role');
        $status = $request->get('status');

        $query = User::query()
            ->select(['id','name','email','contact_number','status','role','created_at','updated_at']);

        if ($q !== '') {
            $query->where(function($w) use ($q){
                $w->where('name','like',"%{$q}%")
                  ->orWhere('email','like',"%{$q}%")
                  ->orWhere('contact_number','like',"%{$q}%");
            });
        }
        if ($role && $role !== 'all')     $query->where('role', $role);
        if ($status && $status !== 'all') $query->where('status', strtolower($status));

        return DataTables::of($query)
            ->addColumn('actions', function(User $u){
                return [
                    'update' => route('boss.user.update', $u),
                    'toggle' => route('boss.user.disable', $u),
                ];
            })
            ->toJson();
    }

    public function storeUser(Request $request)
    {
        if (!Auth::user()->hasRole('boss')) abort(403, 'Unauthorized');

        $roles = implode(',', $this->allowedRoles());

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'role'           => ["required","in:$roles"],
            'password'       => [ 'required', Password::min(8)->mixedCase()->numbers()->symbols() ],
            'status'         => ['nullable','in:active,inactive'],
        ]);

        $user = User::create([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'role'           => $validated['role'],
            'password'       => Hash::make($validated['password']),
            'status'         => strtolower($validated['status'] ?? 'active'),
        ]);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'User created successfully.', 'user' => $user], 201)
            : back()->with('success', 'User created successfully.');
    }

    /** 更新用户（状态统一写小写） */
    public function updateUser(Request $request, User $user)
    {
        if (!Auth::user()->hasRole('boss')) abort(403, 'Unauthorized');

        $roles = implode(',', $this->allowedRoles());

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'role'           => ["required","in:$roles"],
            'password'       => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols()],
            'status'         => ['required','in:active,inactive'],
        ]);

        $user->fill([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'role'           => $validated['role'],
            'status'         => strtolower($validated['status']),
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return $request->expectsJson()
            ? response()->json(['success'=>true,'message'=>'User updated successfully.','user'=>$user])
            : redirect()->route('boss.manageuser', $request->only('q','role','status'))
                        ->with('success', 'User updated successfully.');
    }

    /** 启/停用切换（大小写安全） */
    public function disableUser(Request $request, User $user)
    {
        if (!Auth::user()->hasRole('boss')) abort(403, 'Unauthorized');

        $current = strtolower((string)$user->status);
        $user->status = $current === 'active' ? 'inactive' : 'active';
        $user->save();

        return $request->expectsJson()
            ? response()->json(['success'=>true,'status'=>$user->status,'message'=>'User status updated successfully.'])
            : redirect()->route('boss.manageuser', $request->only('q','role','status'))
                        ->with('success', 'User status updated successfully.');
    }
}
