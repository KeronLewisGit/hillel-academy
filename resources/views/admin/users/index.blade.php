<x-layouts.admin title="Staff accounts">
    <x-slot:actions><a href="{{ route('admin.users.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />Add staff</a></x-slot:actions>
    <section class="a-card overflow-hidden"><table class="data-table"><thead><tr><th>Name</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody>
        @foreach ($users as $u)
            <tr><td class="font-semibold">{{ $u->name }}<span class="block text-xs font-normal text-ink-3">{{ $u->email }}</span></td><td>{{ $u->role->label() }}<span class="block text-xs text-ink-3">{{ $u->role->description() }}</span></td>
                <td><x-chip :tone="$u->active ? 'good' : 'muted'" :icon="$u->active ? 'check' : 'dot'">{{ $u->active ? 'Active' : 'Deactivated' }}</x-chip></td><td class="r"><a class="a-btn a-btn-ghost" href="{{ route('admin.users.edit', $u) }}">Edit</a></td></tr>
        @endforeach
    </tbody></table></section>
</x-layouts.admin>
