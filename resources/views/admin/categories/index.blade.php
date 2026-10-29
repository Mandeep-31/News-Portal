<!doctype html><html><head><meta charset="utf-8"><title>Manage categories</title></head><body>
<h1>Manage categories</h1><p><a href="{{ route('admin.dashboard') }}">Dashboard</a> | <a href="{{ route('admin.categories.create') }}">Add category</a></p>
@if(session('status'))<p>{{ session('status') }}</p>@endif
@if ($errors->any())<ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<p>{{ $categories->total() }} categories</p>
@if($categories->isEmpty())<p>No categories yet. Add one before creating news.</p>@else
<table border="1" cellpadding="8"><thead><tr><th>Name</th><th>Slug</th><th>News items</th><th>Actions</th></tr></thead><tbody>
@foreach($categories as $category)<tr><td>{{ $category->name }}</td><td>{{ $category->slug }}</td><td>{{ $category->posts_count }}</td><td><a href="{{ route('admin.categories.edit', $category) }}">Edit</a>
<form method="POST" action="{{ route('admin.categories.destroy', $category) }}" style="display:inline" onsubmit="return confirm('Delete this category?')">@csrf @method('DELETE')<button>Delete</button></form></td></tr>@endforeach
</tbody></table>{{ $categories->links() }}@endif</body></html>
