<!doctype html><html><head><meta charset="utf-8"><title>Add category</title></head><body>
<h1>Add category</h1><p><a href="{{ route('admin.categories.index') }}">Back to categories</a></p>
@if ($errors->any())<ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="POST" action="{{ route('admin.categories.store') }}">@csrf
<p><label>Name <input name="name" required maxlength="80" value="{{ old('name') }}"></label></p>
<p>News can only use categories created here.</p>
<button type="submit">Create category</button></form></body></html>
