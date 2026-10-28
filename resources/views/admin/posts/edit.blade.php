<!doctype html><html><head><meta charset="utf-8"><title>Edit news</title></head><body>
<h1>Edit news</h1><p><a href="{{ route('admin.posts.index') }}">Back to news</a></p>
@if ($errors->any())<ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="POST" action="{{ route('admin.posts.update', $post) }}" enctype="multipart/form-data">@csrf @method('PUT')
@include('admin.posts.form', ['post' => $post])
<button name="publish_now" value="1">Upload now</button> <button type="submit">Save or schedule</button></form></body></html>
