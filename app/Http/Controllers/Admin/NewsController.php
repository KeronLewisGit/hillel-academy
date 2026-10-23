<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        return view('admin.news.index', ['posts' => NewsPost::orderByRaw('published_at is null desc')->orderByDesc('published_at')->paginate(25)]);
    }

    public function create(): View
    {
        return view('admin.news.form', ['post' => new NewsPost(['published_at' => now()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Slug::unique(NewsPost::class, $data['title'], 'news');
        $data['user_id'] = $request->user()?->id;
        NewsPost::create($data);

        return to_route('admin.news.index')->with('status', 'Story saved.');
    }

    public function edit(NewsPost $post): View
    {
        return view('admin.news.form', ['post' => $post]);
    }

    public function update(Request $request, NewsPost $post): RedirectResponse
    {
        $post->update($this->validated($request, $post));

        return to_route('admin.news.index')->with('status', 'Story saved.');
    }

    public function destroy(NewsPost $post): RedirectResponse
    {
        if ($post->image_path) {
            Storage::disk('uploads')->delete($post->image_path);
        }
        $post->delete();

        return to_route('admin.news.index')->with('status', 'Story deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?NewsPost $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'body' => ['required', 'string', 'max:30000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144', 'dimensions:min_width=800'],
            'image_alt' => ['nullable', 'required_with:image', 'string', 'max:200'],
            'publish' => ['required', 'in:now,draft,date'],
            'published_at' => ['nullable', 'required_if:publish,date', 'date'],
            'remove_image' => ['sometimes', 'boolean'],
        ], ['image.dimensions' => 'Photos need to be at least 800 pixels wide.', 'image_alt.required_with' => 'Describe the photo for people using screen readers.']);

        $data['published_at'] = match ($data['publish']) {
            'now' => $post?->published_at && $post->published_at->isPast() ? $post->published_at : now(),
            'date' => $data['published_at'],
            default => null,
        };
        if ($file = $request->file('image')) {
            if ($post?->image_path) {
                Storage::disk('uploads')->delete($post->image_path);
            }
            $data['image_path'] = $file->store('news', 'uploads');
        } elseif ($request->boolean('remove_image') && $post?->image_path) {
            Storage::disk('uploads')->delete($post->image_path);
            $data['image_path'] = null;
            $data['image_alt'] = null;
        }
        unset($data['image'], $data['publish'], $data['remove_image']);

        return $data;
    }
}
