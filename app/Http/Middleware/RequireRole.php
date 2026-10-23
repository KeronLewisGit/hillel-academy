<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** `role:admissions` lets admissions staff and admins through; `role:admin` admins only. */
class RequireRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasRole(Role::from($role)), 403, 'Your account does not have access to this area.');

        return $next($request);
    }
}
