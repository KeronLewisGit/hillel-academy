<x-layouts.admin title="Email templates">
    <x-slot:actions><a href="{{ route('admin.templates.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />New template</a></x-slot:actions>
    <p class="mb-4 max-w-3xl text-ink-2">Automatic templates go out by themselves at each step of the pipeline. Others are for one-off messages from an application’s page. Placeholders: <code class="text-sm">{{ implode(' ', \App\Models\MessageTemplate::placeholders()) }}</code></p>
    <section class="a-card overflow-hidden"><div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Name</th><th>Sent</th><th>Subject</th><th></th></tr></thead><tbody>
        @foreach ($templates as $t)
            <tr><td class="font-semibold">{{ $t->name }}</td><td class="text-sm">@if ($t->key)<x-chip tone="neutral" icon="mail">{{ \App\Models\MessageTemplate::AUTOMATIC[$t->key] ?? 'Automatic' }}</x-chip>@else<span class="text-ink-3">By hand</span>@endif</td><td class="text-sm text-ink-2">{{ $t->subject }}</td><td class="r"><a class="a-btn a-btn-ghost" href="{{ route('admin.templates.edit', $t) }}">Edit</a></td></tr>
        @endforeach
    </tbody></table></div></section>
</x-layouts.admin>
