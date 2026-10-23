<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Mail\FamilyLoginLink;
use App\Models\Family;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Password-free sign-in. Asking for a link always gives the same answer, whether
 * or not the address belongs to a family, so the form reveals nothing.
 */
class LoginController extends Controller
{
    public function show(): View
    {
        return view('family.login');
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        $email = strtolower($data['email']);

        if ($family = Family::query()->where('email', $email)->first()) {
            $url = $family->loginUrl();
            try {
                Mail::to($family->email)->send(new FamilyLoginLink($family->name, $url));
            } catch (\Throwable $e) {
                report($e);
            }
            if (hillel('demo_links')) {
                $request->session()->flash('demo_link', $url);
            }
        }

        $request->session()->put('check_email', $email);

        return to_route('family.login.sent');
    }

    public function sent(Request $request): View
    {
        return view('pages.check-email', ['email' => $request->session()->get('check_email'), 'demoLink' => $request->session()->get('demo_link'), 'signIn' => true]);
    }

    public function consume(Request $request, string $token): RedirectResponse
    {
        $family = Family::findByLoginToken($token);
        if (! $family) {
            return to_route('family.login')->withErrors(['email' => 'That sign-in link has expired or has already been used. Enter your email for a new one.']);
        }

        $family->consumeLoginToken();
        Auth::guard('web')->logout(); // a staff session in the same browser never mixes with a family one
        Auth::guard('family')->login($family, remember: true);
        $request->session()->regenerate();

        $draft = $family->applications()->where('status', 'draft')->first();

        return $draft && $family->applications()->count() === 1
            ? to_route('family.applications.step', [$draft, max(1, min($draft->step, 5))])
            : to_route('family.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('family')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('family.login')->with('status', 'You have signed out.');
    }
}
