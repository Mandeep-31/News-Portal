<p><label>Title <input name="title" required maxlength="255" value="{{ old('title', $post->title) }}"></label></p>
<p><label>Category <select name="category_id" required><option value="">Choose a category</option>
@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $post->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach
</select></label> <a href="{{ route('admin.categories.index') }}">Manage categories</a></p>
<p><label>Summary <textarea name="excerpt" maxlength="500">{{ old('excerpt', $post->excerpt) }}</textarea></label></p>
<p><label>Description <textarea name="body" required rows="12">{{ old('body', $post->body) }}</textarea></label></p>
<p><label>Image caption (optional) <input name="caption" maxlength="500" value="{{ old('caption', $post->caption) }}"></label></p>
<p><label>Author (optional) <input name="author" maxlength="120" value="{{ old('author', $post->author) }}"></label></p>
<p><label>Priority (0–10; higher appears first) <input type="number" name="priority" min="0" max="10" value="{{ old('priority', $post->priority ?? 0) }}" required></label></p>
<p><label>Schedule for (leave blank for draft; maximum 24 hours ahead)
<input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}"></label></p>
<small>Upload now records the current date and time. News is marked as added by Admin automatically.</small>
<p><label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $post->is_featured))> Mark as homepage feature</label></p>
@if($post->exists && count($post->images ?? array_filter([$post->featured_image])))<fieldset><legend>Current images</legend>
@foreach($post->images ?? array_filter([$post->featured_image]) as $path)<label style="display:inline-block;margin:8px"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" width="120"><br><input type="checkbox" name="remove_images[]" value="{{ $path }}"> Remove</label>@endforeach
</fieldset>@endif
<p><label>Images (up to three total, 5 MB each; JPG, PNG or WebP) <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple></label></p>
