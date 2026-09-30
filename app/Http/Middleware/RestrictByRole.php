<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RestrictByRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
    {
        // Allow commas or pipes in the middleware param
        // e.g. role:artist,head-artist  OR  role:artist|head-artist
        $roles = preg_split('/[,\|]/', implode(',', $roles));
        $roles = array_values(array_filter(array_map('trim', $roles)));

        // Catch typos like role:printing (real role is operations-printing) while developing.
        if (! app()->isProduction() && $unknown = array_diff($roles, Role::values())) {
            throw new \InvalidArgumentException('Unknown role in route middleware: ' . implode(', ', $unknown));
        }

        if (!Auth::check()) {
            abort(403, 'Unauthorized action.');
        }

        $userRole = trim((string) Auth::user()->role);

        if (!in_array($userRole, $roles, true)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
