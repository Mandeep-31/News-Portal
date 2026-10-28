<!doctype html><html><head><meta charset="utf-8"><title>Edit category</title></head><body>
<h1>Edit category</h1><p><a href="{{ route('admin.categories.index') }}">Back to categories</a></p>
@if ($errors->any())<ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="POST" action="{{ route('admin.categories.update', $category) }}">@csrf @method('PUT')
<p><label>Name <input name="name" required maxlength="80" value="{{ old('name', $category->name) }}"></label></p>
<button type="submit">Save category</button></form></body></html>
