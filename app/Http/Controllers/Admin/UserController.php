<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::orderByDesc('active')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['role' => Role::Admissions, 'active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        User::create($data);

        return to_route('admin.users.index')->with('status', 'Staff account created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        if ($user->is($request->user()) && ($data['role'] !== Role::Admin->value || ! $data['active'])) {
            return back()->withErrors(['role' => 'You can’t remove your own administrator access.']);
        }
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $user->update($data);

        return to_route('admin.users.index')->with('status', 'Staff account saved.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 403, 'You can’t delete your own account.');
        $user->update(['active' => false]);

        return to_route('admin.users.index')->with('status', $user->name.' deactivated.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:10', 'max:200'],
        ]);
        $data['email'] = strtolower($data['email']);
        $data['active'] = $request->boolean('active');

        return $data;
    }
}
