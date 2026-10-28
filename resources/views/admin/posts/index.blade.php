<!doctype html><html><head><meta charset="utf-8"><title>Manage news</title></head><body>
<h1>Manage news</h1><p><a href="{{ route('admin.dashboard') }}">Dashboard</a> | <a href="{{ route('admin.posts.create') }}">Add news</a></p>
@if(session('status'))<p>{{ session('status') }}</p>@endif
@if($posts->isEmpty())<p>No news yet.</p>@else
<table border="1" cellpadding="8"><thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Added by</th><th>Publication</th><th>Priority</th><th>Actions</th></tr></thead><tbody>
@foreach($posts as $post)<tr><td>{{ $post->title }}</td><td>{{ $post->category?->name ?? '—' }}</td><td>{{ $post->author ?? '—' }}</td><td>{{ $post->added_by }}</td><td>{{ $post->published_at?->format('Y-m-d H:i') ?? 'Draft' }} @if($post->is_published && $post->published_at?->isFuture()) (scheduled) @endif</td><td>{{ $post->priority }} @if($post->is_featured) · Homepage feature @endif</td><td><a href="{{ route('admin.posts.edit', $post) }}">Edit</a>
<form method="POST" action="{{ route('admin.posts.destroy', $post) }}" style="display:inline" onsubmit="return confirm('Delete this news?')">@csrf @method('DELETE')<button>Delete</button></form></td></tr>@endforeach
</tbody></table>{{ $posts->links() }}@endif</body></html>
