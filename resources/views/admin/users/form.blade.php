<x-layouts.admin :title="$user->exists ? 'Edit staff account' : 'Add staff'">
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="a-card grid max-w-2xl gap-3 p-5">@csrf @if ($user->exists) @method('PUT') @endif
        <label class="text-sm font-medium" for="name">Name</label><input id="name" name="name" class="a-input" value="{{ old('name', $user->name) }}" required>
        <label class="text-sm font-medium" for="email">Email</label><input id="email" type="email" name="email" class="a-input" value="{{ old('email', $user->email) }}" required>
        <fieldset class="grid gap-2"><legend class="text-sm font-medium">Role</legend>
            @foreach (\App\Enums\Role::cases() as $r)<label class="choice"><input type="radio" name="role" value="{{ $r->value }}" @checked(old('role', $user->role?->value) === $r->value)><span><span class="font-semibold">{{ $r->label() }}</span><span class="block text-sm text-ink-3">{{ $r->description() }}</span></span></label>@endforeach
        </fieldset>
        <label class="text-sm font-medium" for="password">{{ $user->exists ? 'New password (leave blank to keep)' : 'Password' }} <span class="font-normal text-ink-3">(at least 10 characters)</span></label><input id="password" type="password" name="password" class="a-input" autocomplete="new-password" @unless($user->exists) required @endunless minlength="10">
        <label class="flex items-center gap-2"><input type="checkbox" name="active" value="1" @checked(old('active', $user->active)) class="h-4 w-4"> Active (can sign in)</label>
        <div class="flex flex-wrap gap-2"><button class="a-btn a-btn-primary">Save</button><a class="a-btn a-btn-ghost" href="{{ route('admin.users.index') }}">Cancel</a></div>
    </form>
</x-layouts.admin>
