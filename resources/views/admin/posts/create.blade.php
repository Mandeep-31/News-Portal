<!doctype html><html><head><meta charset="utf-8"><title>Add news</title></head><body>
<h1>Add news</h1><p><a href="{{ route('admin.posts.index') }}">Back to news</a></p>
@if ($errors->any())<ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="POST" action="{{ route('admin.posts.store') }}" enctype="multipart/form-data">@csrf
@include('admin.posts.form', ['post' => $post])
<button name="publish_now" value="1">Upload now</button> <button type="submit">Save or schedule</button></form></body></html>
