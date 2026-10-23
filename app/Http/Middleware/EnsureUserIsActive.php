<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** A deactivated staff account is signed out on its next request. */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user instanceof User && ! $user->active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return to_route('login')->withErrors(['email' => 'This account has been deactivated.']);
        }

        return $next($request);
    }
}
