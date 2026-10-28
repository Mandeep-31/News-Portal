<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $post->title }}</title></head><body>
<p><a href="{{ route('home') }}">All news</a></p><article><h1>{{ $post->title }}</h1><p><strong>{{ $post->category?->name }}</strong> · {{ $post->published_at?->format('Y-m-d H:i') }} @if($post->author) · {{ $post->author }}@endif</p>
@foreach($post->images ?? array_filter([$post->featured_image]) as $image)<figure><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}" alt="{{ $post->caption ?? $post->title }}" style="max-width:100%;height:auto">@if($post->caption)<figcaption>{{ $post->caption }}</figcaption>@endif</figure>@endforeach
<div>{{ $post->body }}</div></article></body></html>
