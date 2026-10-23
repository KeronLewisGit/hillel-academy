<x-layouts.admin :title="$event->exists ? 'Edit event' : 'Add event'">
    <form method="POST" action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}" class="a-card grid max-w-3xl gap-3 p-5">@csrf @if ($event->exists) @method('PUT') @endif
        <label class="text-sm font-medium" for="title">Title</label><input id="title" name="title" class="a-input" value="{{ old('title', $event->title) }}" required maxlength="200">
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="text-sm font-medium" for="starts_at">Starts</label><input id="starts_at" type="datetime-local" name="starts_at" class="a-input mt-1 w-full" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" required></div>
            <div><label class="text-sm font-medium" for="ends_at">Ends <span class="font-normal text-ink-3">(optional)</span></label><input id="ends_at" type="datetime-local" name="ends_at" class="a-input mt-1 w-full" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}"></div>
        </div>
        <label class="flex items-center gap-2"><input type="checkbox" name="all_day" value="1" @checked(old('all_day', $event->all_day)) class="h-4 w-4"> All-day (times are not shown)</label>
        <label class="text-sm font-medium" for="category">Type</label><select id="category" name="category" class="a-input">@foreach (\App\Enums\EventCategory::cases() as $c)<option value="{{ $c->value }}" @selected(old('category', $event->category?->value) === $c->value)>{{ $c->label() }}</option>@endforeach</select>
        <label class="text-sm font-medium" for="location">Where</label><input id="location" name="location" class="a-input" value="{{ old('location', $event->location) }}" maxlength="160">
        <label class="text-sm font-medium" for="description">Details</label><textarea id="description" name="description" rows="5" class="a-input">{{ old('description', $event->description) }}</textarea>
        <label class="flex items-center gap-2"><input type="checkbox" name="published" value="1" @checked(old('published', $event->published)) class="h-4 w-4"> Show on the public calendar</label>
        <div class="flex flex-wrap gap-2"><button class="a-btn a-btn-primary">Save</button><a class="a-btn a-btn-ghost" href="{{ route('admin.events.index') }}">Cancel</a></div>
    </form>
    @if ($event->exists)
        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="mt-4" onsubmit="return confirm('Delete this event?')">@csrf @method('DELETE')<button class="a-btn a-btn-ghost text-[color:var(--critical-text)]"><x-icon name="trash" class="h-4 w-4" />Delete event</button></form>
    @endif
</x-layouts.admin>
