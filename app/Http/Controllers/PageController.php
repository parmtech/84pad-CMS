<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        return view('pages.index', ['pages' => Page::query()->latest('updated_at')->paginate(20)]);
    }

    public function create(): View
    {
        return view('pages.form', ['page' => new Page()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        $page = Page::create($data);

        return redirect()->route('pages.edit', $page)->with('notice', 'Page saved as a draft.');
    }

    public function edit(Page $page): View
    {
        return view('pages.form', compact('page'));
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));

        return redirect()->route('pages.edit', $page)->with('notice', 'Page updated.');
    }

    public function preview(Page $page): Response
    {
        return response()
            ->view('pages.public', ['page' => $page, 'previewMode' => true])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'private, no-store');
    }

    public function publish(Page $page): RedirectResponse
    {
        $page->update([
            'status' => $page->status === 'published' ? 'draft' : 'published',
            'published_at' => $page->status === 'published' ? null : now(),
        ]);

        return redirect()->route('pages.edit', $page)->with('notice', $page->status === 'published' ? 'Page published.' : 'Page returned to draft.');
    }

    public function show(string $slug): View
    {
        $page = Page::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();

        return view('pages.public', ['page' => $page, 'previewMode' => false]);
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'body' => ['nullable', 'string', 'max:1000000'],
        ]);

        $data['slug'] = filled($data['slug'] ?? null)
            ? Str::lower($data['slug'])
            : Str::slug($data['title']);
        $data['body'] ??= '';

        validator($data, ['slug' => ['required', Rule::unique('pages', 'slug')->ignore($page?->id)]])->validate();

        return $data;
    }
}

