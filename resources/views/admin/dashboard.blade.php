<!doctype html><html><head><meta charset="utf-8"><title>Admin Dashboard</title></head><body>
<h1>News control room</h1>
<nav><a href="{{ route('admin.posts.index') }}">Manage news</a> | <a href="{{ route('admin.posts.create') }}">Add news</a> | <a href="{{ route('admin.categories.index') }}">Manage categories</a> | <a href="{{ route('admin.categories.create') }}">Add category</a> | <a href="{{ route('admin.banners.index') }}">Manage banners</a> | <a href="{{ route('admin.banners.create') }}">Add banner</a>
<form method="POST" action="{{ route('admin.logout') }}" style="display:inline">@csrf <button type="submit">Logout</button></form></nav>
<ul><li>Total news: {{ $counts['total'] }}</li><li>Live: {{ $counts['live'] }}</li><li>Scheduled: {{ $counts['scheduled'] }}</li><li>Categories: {{ $counts['categories'] }}</li><li>Banners: {{ $counts['banners'] }} ({{ $counts['active_banners'] }} active)</li></ul>
</body></html>
