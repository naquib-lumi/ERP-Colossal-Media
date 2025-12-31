<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class BossManageUserController extends Controller
{
    private function allowedRoles(): array
    {
        return [
            'admin',
            'salesperson',
            'head-salesperson',
            'head-artist',
            'artist',
            'operations-printing',
            'operations-furnishing',
            'operations-dispatch-control',
            'operations-delivery-installation',
            'data-entry',
            'installation',
        ];
    }
    
    public function manageUser(Request $request)
    {
        if (!Auth::user()->hasRole('boss')) abort(403, 'Unauthorized');

        $users = User::query()
        ->where('role', '!=', 'admin')
        // ->where('role', '!=', 'boss')
        ->orderBy('name')
        ->get();

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
            ->select(['id','name','email','contact_number','status','role','created_at','updated_at'])
            ->where('role', '!=', 'admin')
            ->where('role', '!=', 'boss');

        if ($q !== '') {
            $query->where(function($w) use ($q){
                $w->where('name','like',"%{$q}%")
                  ->orWhere('email','like',"%{$q}%")
                  ->orWhere('contact_number','like',"%{$q}%");
            });
        }
        if ($role && $role !== 'all' && $role !== 'admin') {
            $query->where('role', $role);
        }
        if ($status && $status !== 'all') {
            $query->whereRaw('LOWER(status)=?', [strtolower($status)]);
        }

        return DataTables::of($query)
            ->addColumn('actions', fn(User $u) => [
                'update' => route('boss.user.update', $u),
                'toggle' => route('boss.user.disable', $u),
            ])->toJson();
    }

    public function storeUser(Request $request)
    {
        if (!Auth::user()->hasRole('boss')) abort(403, 'Unauthorized');

        $roles = $this->allowedRoles();

        $data = $request->validate([
            'name'           => ['required','string','max:255'],
            'email'          => ['required','email','max:255','unique:users,email'],
            'contact_number' => ['nullable','string','max:30'],
            'role'           => ['required', Rule::in($roles)],
            'password'       => ['nullable','string','min:8'],
            'status'         => ['nullable','in:active,inactive'],
        ]);

        $plain = $data['password'] ?: Str::password(12);
        $user  = User::create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'contact_number' => $data['contact_number'] ?? null,
            'role'           => $data['role'],
            'password'       => Hash::make($plain),
            'status'         => strtolower($data['status'] ?? 'active'),
        ]);

        return back()->with('success', 'User created. Temp password: '.$plain);
    }

    /** 更新用户（状态统一写小写） */
    public function updateUser(Request $request, User $user)
    {
        if (!Auth::user()->hasRole('boss')) abort(403, 'Unauthorized');

        $roles = $this->allowedRoles();

        $data = $request->validate([
            'name'           => ['required','string','max:255'],
            'email'          => ['required','email','max:255','unique:users,email,'.$user->id],
            'contact_number' => ['nullable','string','max:30'],
            'role'           => ['sometimes', 'required', Rule::in($roles)],
            'password'       => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols()],
            'status'         => ['required','in:active,inactive'],
        ]);

        if (auth()->id() === $user->id && strtolower($data['status']) === 'inactive') {
            return back()->withErrors(['status' => 'You cannot deactivate your own account.']);
        }
        if (auth()->id() === $user->id && $user->role === 'admin' && $request->filled('role') && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'You cannot downgrade your own admin role.']);
        }

        $user->fill([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'contact_number' => $data['contact_number'] ?? null,
            'status'         => strtolower($data['status']),
        ]);

        if ($request->filled('role')) {
            $user->role = $data['role'];
        }

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return $request->expectsJson()
            ? response()->json(['success'=>true,'message'=>'User updated successfully.','user'=>$user])
            : redirect()->route('boss.manageuser', $request->only('q','role','status'))
                        ->with('success', 'User updated successfully.');
    }

    /** 重置密码到 password123 */
    public function resetPassword(Request $request, User $user)
    {
        if (!Auth::user()->hasRole('boss')) abort(403, 'Unauthorized');

        $user->password = Hash::make('password123');
        $user->save();

        return back()->with('success', 'Password reset to password123.');
    }

    /** 启/停用切换（大小写安全） */
    public function disableUser(Request $request, User $user)
    {
        if (!Auth::user()->hasRole('boss')) abort(403, 'Unauthorized');

        if (auth()->id() === $user->id) {
            return back()->withErrors(['status' => 'You cannot deactivate your own account.']);
        }

        $user->status = strtolower((string)$user->status) === 'active' ? 'inactive' : 'active';
        $user->save();

        return $request->expectsJson()
            ? response()->json(['success'=>true,'status'=>$user->status,'message'=>'User status updated successfully.'])
            : redirect()->route('boss.manageuser', $request->only('q','role','status'))
                        ->with('success', 'User status updated successfully.');
    }
}
