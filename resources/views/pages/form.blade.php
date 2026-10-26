<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>{{ $page->exists ? 'Edit' : 'New' }} page · 84PAD</title>
<style>body{margin:0;background:#080d1b;color:#e7efff;font:15px system-ui,sans-serif}.wrap{max-width:900px;margin:42px auto;padding:0 20px}a{color:#87eafa}label{display:block;margin:20px 0 7px}input,textarea{box-sizing:border-box;width:100%;padding:12px;border:1px solid #59708e;border-radius:8px;background:#111c32;color:#fff;font:inherit}textarea{min-height:45vh;resize:vertical}.muted{color:#a6b8d1}button{margin-top:18px;padding:11px 16px;border-radius:8px;background:#83e8f4;border:0;font-weight:700}.error{color:#ff9db5}</style></head><body><main class="wrap">
<p><a href="{{ route('pages.index') }}">← Pages</a></p><h1>{{ $page->exists ? 'Edit page' : 'New page' }}</h1><p class="muted">Page text is displayed as plain text for safety; HTML and scripts are not executed.</p>
@if($errors->any())<div class="error" role="alert"><p>Please fix these fields:</p><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ $page->exists ? route('pages.update', $page) : route('pages.store') }}">@csrf @if($page->exists)@method('put')@endif
<label for="title">Title</label><input id="title" name="title" maxlength="160" required value="{{ old('title', $page->title) }}">
<label for="slug">URL name (optional)</label><input id="slug" name="slug" maxlength="180" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{{ old('slug', $page->slug) }}" placeholder="about-us"><p class="muted">Use lowercase letters, numbers and hyphens.</p>
<label for="body">Page text</label><textarea id="body" name="body">{{ old('body', $page->body) }}</textarea><button type="submit">Save draft</button></form>
@if($page->exists)<form method="post" action="{{ route('pages.publish', $page) }}">@csrf @method('patch')<button type="submit">{{ $page->status === 'published' ? 'Unpublish' : 'Publish' }}</button></form>@endif
</main></body></html>

