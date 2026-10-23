<x-layouts.admin :title="$template->exists ? 'Edit template' : 'New template'">
    <form method="POST" action="{{ $template->exists ? route('admin.templates.update', $template) : route('admin.templates.store') }}" class="a-card grid max-w-3xl gap-3 p-5">@csrf @if ($template->exists) @method('PUT') @endif
        @if ($template->key)<p class="rounded-lg bg-surface-2 p-3 text-sm">Sent automatically: {{ \App\Models\MessageTemplate::AUTOMATIC[$template->key] ?? $template->key }}.@if ($template->key === 'docs_returned') Extra placeholders: {document} {reason}.@endif</p>@endif
        <label class="text-sm font-medium" for="name">Name</label><input id="name" name="name" class="a-input" value="{{ old('name', $template->name) }}" required>
        <label class="text-sm font-medium" for="subject">Subject</label><input id="subject" name="subject" class="a-input" value="{{ old('subject', $template->subject) }}" required>
        <label class="text-sm font-medium" for="body">Message</label><textarea id="body" name="body" rows="12" class="a-input" required>{{ old('body', $template->body) }}</textarea>
        <p class="text-xs text-ink-3">Leave a blank line between paragraphs. Placeholders: {{ implode(' ', \App\Models\MessageTemplate::placeholders()) }}</p>
        <div class="flex flex-wrap gap-2"><button class="a-btn a-btn-primary">Save</button><a class="a-btn a-btn-ghost" href="{{ route('admin.templates.index') }}">Cancel</a></div>
    </form>
    @if ($template->exists && ! $template->key)
        <form method="POST" action="{{ route('admin.templates.destroy', $template) }}" class="mt-4" onsubmit="return confirm('Delete this template?')">@csrf @method('DELETE')<button class="a-btn a-btn-ghost text-[color:var(--critical-text)]"><x-icon name="trash" class="h-4 w-4" />Delete template</button></form>
    @endif
</x-layouts.admin>
