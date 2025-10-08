<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrintingProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // 从 users 表取值（注意使用 contact_number）
        $base = [
            'name'     => $user->name ?? '',
            'email'    => $user->email ?? '',
            'phone'    => $this->readUserColumn($user->id, 'contact_number'), // 👈 修正
            'avatar'   => $this->readUserColumn($user->id, 'avatar'),
            'timezone' => $this->readUserColumn($user->id, 'timezone') ?? config('app.timezone'),
        ];

        $prefs = $this->loadPrintingPrefs($user->id);

        return view('printing.profile', [
            'user'    => $user,
            'base'    => $base,
            'prefs'   => $prefs,
            'options' => [
                'date_formats' => ['MM/DD/YYYY','DD/MM/YYYY','YYYY-MM-DD'],
                'ui_densities' => ['compact','comfortable','spacious'],
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // 表单校验：允许传 phone 或 contact_number（都会写到 contact_number）
        $data = $request->validate([
            'name'            => ['nullable','string','max:255'],
            'phone'           => ['nullable','string','max:50'],
            'contact_number'  => ['nullable','string','max:50'],
            'timezone'        => ['nullable','string','max:64'],
            'avatar'          => ['nullable','image','max:4096'],

            'date_format'     => ['nullable','string','in:MM/DD/YYYY,DD/MM/YYYY,YYYY-MM-DD'],
            'ui_density'      => ['nullable','string','in:compact,comfortable,spacious'],
            'default_printer' => ['nullable','string','max:255'],
            'default_cutter'  => ['nullable','string','max:255'],
            'notif_email'     => ['nullable','boolean'],
            'notif_push'      => ['nullable','boolean'],
        ]);

        $data['notif_email'] = (bool)($data['notif_email'] ?? false);
        $data['notif_push']  = (bool)($data['notif_push']  ?? false);

        // 头像
        $publicUrl = null;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $ext  = $file->getClientOriginalExtension() ?: 'png';
            $path = $file->storeAs('public/avatars', 'u'.$user->id.'-'.Str::random(8).'.'.$ext);
            $publicUrl = Storage::url($path);
        }

        DB::beginTransaction();
        try {
            // === 更新 users ===
            $updateUsers = [];

            if (array_key_exists('name', $data) && $this->columnExists('users', 'name')) {
                $updateUsers['name'] = $data['name'];
            }
            // 将 phone / contact_number 统一写入 contact_number
            $incomingPhone = $data['contact_number'] ?? $data['phone'] ?? null;
            if (!is_null($incomingPhone) && $this->columnExists('users','contact_number')) {
                $updateUsers['contact_number'] = $incomingPhone; // 👈 修正
            }
            if (array_key_exists('timezone', $data) && $this->columnExists('users','timezone')) {
                $updateUsers['timezone'] = $data['timezone'];
            }
            if ($publicUrl && $this->columnExists('users','avatar')) {
                $updateUsers['avatar'] = $publicUrl;
            }

            if (!empty($updateUsers)) {
                DB::table('users')->where('id', $user->id)->update($updateUsers);
            }

            // === 更新 printing_profiles（如果有表）===
            if ($this->tableExists('printing_profiles')) {
                $payload = array_merge([
                    'user_id'    => $user->id,
                    'updated_at' => now(),
                ], $this->onlyPrefs($data));

                if ($publicUrl && $this->columnExists('printing_profiles','avatar')) {
                    $payload['avatar'] = $publicUrl;
                }

                $exists = DB::table('printing_profiles')->where('user_id',$user->id)->exists();
                if ($exists) {
                    DB::table('printing_profiles')->where('user_id',$user->id)->update($payload);
                } else {
                    $payload['created_at'] = now();
                    DB::table('printing_profiles')->insert($payload);
                }
            }

            DB::commit();
            return redirect()->route('printing.profile')->with('status', 'Profile updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['update' => $e->getMessage()])->withInput();
        }
    }

    /* ----------------- Helpers ----------------- */

    private function tableExists(string $table): bool
    {
        try { return Schema::hasTable($table); } catch (\Throwable $e) { return false; }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            return $this->tableExists($table) && Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function readUserColumn(int $userId, string $column)
    {
        if (!$this->columnExists('users',$column)) return null;
        return DB::table('users')->where('id',$userId)->value($column);
    }

    private function loadPrintingPrefs(int $userId): array
    {
        $prefs = [
            'date_format'     => 'MM/DD/YYYY',
            'ui_density'      => 'comfortable',
            'default_printer' => null,
            'default_cutter'  => null,
            'notif_email'     => true,
            'notif_push'      => false,
        ];

        if ($this->tableExists('printing_profiles')) {
            $row = DB::table('printing_profiles')->where('user_id',$userId)->first();
            if ($row) {
                foreach (array_keys($prefs) as $k) {
                    if (isset($row->{$k}) && $row->{$k} !== '') $prefs[$k] = $row->{$k};
                }
            }
        }
        return $prefs;
    }

    private function onlyPrefs(array $data): array
    {
        return [
            'date_format'     => $data['date_format']     ?? null,
            'ui_density'      => $data['ui_density']      ?? null,
            'default_printer' => $data['default_printer'] ?? null,
            'default_cutter'  => $data['default_cutter']  ?? null,
            'notif_email'     => $data['notif_email']     ?? false,
            'notif_push'      => $data['notif_push']      ?? false,
        ];
    }
}
