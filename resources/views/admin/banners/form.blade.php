<p><label>Title <input name="title" required maxlength="255" value="{{ old('title', $banner->title) }}"></label></p>
<p><label>Caption (optional) <textarea name="caption" maxlength="500">{{ old('caption', $banner->caption) }}</textarea></label></p>
<p><label>Link URL (optional) <input type="url" name="link_url" maxlength="2048" value="{{ old('link_url', $banner->link_url) }}"></label></p>
<p><label>Display order (smaller numbers appear first) <input type="number" name="sort_order" min="0" max="1000000" value="{{ old('sort_order', $banner->sort_order ?? 0) }}"></label></p>
<p><input type="hidden" name="is_active" value="0"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner->is_active ?? true))> Active on public dashboard</label></p>
@if($banner->exists)<p>Current image: <img src="{{ $banner->toPublicArray()['image'] }}" alt="" width="180"></p>@endif
<p><label>Banner image (JPG, PNG or WebP; max 5 MB) <input type="file" name="image" accept="image/jpeg,image/png,image/webp" @required(!$banner->exists)></label></p>
