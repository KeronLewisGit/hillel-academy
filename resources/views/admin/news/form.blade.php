@php($mode = old('publish', $post->published_at === null ? 'draft' : ($post->published_at->isFuture() ? 'date' : 'now')))
<x-layouts.admin :title="$post->exists ? 'Edit story' : 'New story'">
    <form method="POST" enctype="multipart/form-data" action="{{ $post->exists ? route('admin.news.update', $post) : route('admin.news.store') }}" class="a-card grid max-w-3xl gap-3 p-5">@csrf @if ($post->exists) @method('PUT') @endif
        <label class="text-sm font-medium" for="title">Headline</label><input id="title" name="title" class="a-input" value="{{ old('title', $post->title) }}" required maxlength="200">
        <label class="text-sm font-medium" for="excerpt">Summary <span class="font-normal text-ink-3">(shown on the news list)</span></label><input id="excerpt" name="excerpt" class="a-input" value="{{ old('excerpt', $post->excerpt) }}" maxlength="400">
        <label class="text-sm font-medium" for="body">Story</label><textarea id="body" name="body" rows="14" class="a-input" required>{{ old('body', $post->body) }}</textarea>
        <p class="text-xs text-ink-3">Leave a blank line between paragraphs.</p>
        <fieldset class="grid gap-2"><legend class="text-sm font-medium">Photo <span class="font-normal text-ink-3">(JPG, PNG or WebP, at least 800 px wide, up to 6 MB)</span></legend>
            @if ($post->imageUrl())<img src="{{ $post->imageUrl() }}" alt="{{ $post->image_alt }}" class="h-40 w-auto rounded-lg object-cover"><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_image" value="1" class="h-4 w-4"> Remove photo</label>@endif
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="a-input" aria-label="Photo">
            <label class="text-sm font-medium" for="image_alt">Describe the photo</label><input id="image_alt" name="image_alt" class="a-input" value="{{ old('image_alt', $post->image_alt) }}" maxlength="200" placeholder="e.g. Grade 6 students at the science fair">
            <p class="text-xs text-ink-3">Please only use photos of students whose parents have agreed to them appearing online.</p>
        </fieldset>
        <fieldset class="grid gap-2"><legend class="text-sm font-medium">Publishing</legend>
            <label class="flex items-center gap-2"><input type="radio" name="publish" value="now" @checked($mode === 'now') class="h-4 w-4"> Published</label>
            <label class="flex items-center gap-2"><input type="radio" name="publish" value="date" @checked($mode === 'date') class="h-4 w-4"> Publish on a date</label>
            <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="a-input max-w-xs" aria-label="Publish date">
            <label class="flex items-center gap-2"><input type="radio" name="publish" value="draft" @checked($mode === 'draft') class="h-4 w-4"> Draft (not on the website)</label>
        </fieldset>
        <div class="flex flex-wrap gap-2"><button class="a-btn a-btn-primary">Save</button><a class="a-btn a-btn-ghost" href="{{ route('admin.news.index') }}">Cancel</a></div>
    </form>
    @if ($post->exists)
        <form method="POST" action="{{ route('admin.news.destroy', $post) }}" class="mt-4" onsubmit="return confirm('Delete this story?')">@csrf @method('DELETE')<button class="a-btn a-btn-ghost text-[color:var(--critical-text)]"><x-icon name="trash" class="h-4 w-4" />Delete story</button></form>
    @endif
</x-layouts.admin>
