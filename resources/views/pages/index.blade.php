<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Pages · 84PAD Control Studio</title>
<style>body{margin:0;background:#080d1b;color:#e7efff;font:15px system-ui,sans-serif}.wrap{max-width:1000px;margin:48px auto;padding:0 20px}a{color:#87eafa}header,.row{display:flex;justify-content:space-between;gap:16px;align-items:center}.row{padding:16px 0;border-bottom:1px solid #ffffff26}.muted{color:#a6b8d1}button{padding:10px 14px;border-radius:8px;background:#83e8f4;border:0;font-weight:700} .actions{display:flex;gap:15px}</style></head><body><main class="wrap">
<header><div><p><a href="{{ route('dashboard') }}">← Control Studio</a></p><h1>Pages</h1><p class="muted">Create drafts and publish simple pages.</p></div><a href="{{ route('pages.create') }}"><button type="button">New page</button></a></header>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
@forelse($pages as $page)<article class="row"><div><strong>{{ $page->title }}</strong><div class="muted">/{{ $page->slug }} · {{ ucfirst($page->status) }} · Updated {{ $page->updated_at->diffForHumans() }}</div></div><div class="actions"><a href="{{ route('pages.edit', $page) }}">Edit</a>@if($page->status === 'published')<a href="{{ route('page.show', $page->slug) }}" target="_blank" rel="noopener">View</a>@endif</div></article>@empty<p class="muted">No pages yet. Create a draft to get started.</p>@endforelse
{{ $pages->links() }}</main></body></html>

