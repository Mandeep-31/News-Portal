<!doctype html><html><head><meta charset="utf-8"><title>Add banner</title></head><body>
<h1>Add banner</h1><p><a href="{{ route('admin.banners.index') }}">Back to banners</a></p>
@if ($errors->any())<ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">@csrf
@include('admin.banners.form', ['banner' => $banner])
<button type="submit">Create banner</button></form></body></html>
