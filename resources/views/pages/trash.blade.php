<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Page trash · 84PAD Control Studio</title>
<style>body{margin:0;background:#080d1b;color:#e7efff;font:15px system-ui,sans-serif}.wrap{max-width:950px;margin:48px auto;padding:0 20px}a{color:#87eafa}.row{display:flex;align-items:center;justify-content:space-between;gap:16px;border-bottom:1px solid #ffffff26;padding:15px 0}.muted{color:#a6b8d1}button{padding:10px 14px;border-radius:8px;background:#83e8f4;border:0;color:#091323;font-weight:700;cursor:pointer}form{margin:0}@media(max-width:600px){.row{align-items:flex-start;flex-direction:column}}</style>
</head><body><main class="wrap">
<p><a href="{{ route('pages.index') }}">← Back to pages</a></p>
<h1>Page trash</h1>
<p class="muted">Restored pages return as private drafts. Previously selected homepages are not automatically reassigned. Page slugs stay reserved while in trash.</p>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
@forelse($pages as $page)
<article class="row">
  <div><strong>{{ $page->title }}</strong><p class="muted">/{{ $page->slug }} · Moved to trash {{ $page->deleted_at->diffForHumans() }}</p></div>
  <form method="post" action="{{ route('pages.restore', $page->id) }}">@csrf<button type="submit" aria-label="Restore {{ $page->title }} as draft">Restore as draft</button></form>
</article>
@empty
<p class="muted">There are no pages in trash.</p>
@endforelse
{{ $pages->links() }}
</main></body></html>
