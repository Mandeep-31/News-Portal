<!doctype html><html><head><meta charset="utf-8"><title>Manage banners</title></head><body>
<h1>Manage banners</h1><p><a href="{{ route('admin.dashboard') }}">Dashboard</a> | <a href="{{ route('admin.banners.create') }}">Add banner</a></p>
@if(session('status'))<p>{{ session('status') }}</p>@endif
@if($banners->isEmpty())<p>No banners yet.</p>@else
<table border="1" cellpadding="8"><thead><tr><th>Image</th><th>Title</th><th>Caption</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@foreach($banners as $banner)<tr><td><img src="{{ $banner->toPublicArray()['image'] }}" alt="" width="160"></td><td>{{ $banner->title }}</td><td>{{ $banner->caption ?? '—' }}</td><td>{{ $banner->sort_order }}</td><td>{{ $banner->is_active ? 'Active' : 'Hidden' }}</td><td><a href="{{ route('admin.banners.edit', $banner) }}">Edit</a>
<form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" style="display:inline" onsubmit="return confirm('Delete this banner?')">@csrf @method('DELETE')<button>Delete</button></form></td></tr>@endforeach
</tbody></table>{{ $banners->links() }}@endif</body></html>
