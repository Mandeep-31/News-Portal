<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>News</title></head><body>
<header><h1>Latest news</h1></header>
@if($banners->isNotEmpty())<section aria-label="Banners"><h2>Featured banners</h2>
@foreach($banners as $banner)<article><a href="{{ $banner['link_url'] ?: '#' }}"><img src="{{ $banner['image'] }}" alt="{{ $banner['title'] }}" style="max-width:100%;height:auto"></a><h3>{{ $banner['title'] }}</h3>@if($banner['caption'])<p>{{ $banner['caption'] }}</p>@endif</article>@endforeach
</section>@endif
@if($featured)<section><h2>Featured story</h2><article><h3><a href="{{ route('news.show', $featured->slug) }}">{{ $featured->title }}</a></h3><p>{{ $featured->excerpt ?: $featured->body }}</p><small>{{ $featured->category?->name }} · {{ $featured->published_at?->format('Y-m-d H:i') }}</small></article></section>@endif
<main><h2>Recent stories</h2>
@forelse($posts as $post)<article><h3><a href="{{ route('news.show', $post->slug) }}">{{ $post->title }}</a></h3><p>{{ $post->excerpt ?: \Illuminate\Support\Str::limit($post->body, 240) }}</p><small>{{ $post->category?->name }} · {{ $post->published_at?->format('Y-m-d H:i') }}</small></article>@empty<p>No published news right now.</p>@endforelse
{{ $posts->links() }}</main>
</body></html>
