<div class="admin-card">
  <h2 style="font-size:15px; font-weight:700; margin-bottom:14px">Thông tin gợi ý truyện</h2>
  <input type="hidden" name="recommendation_tag_ids" value="">
  @php
    $selectedMetadataIds = (array) old('recommendation_tag_ids', isset($comic) ? $comic->tags->pluck('id')->all() : []);
  @endphp
  @foreach(['theme' => 'Theme', 'setting' => 'Setting', 'character' => 'Character', 'tone' => 'Tone', 'relationship' => 'Relationship'] as $category => $label)
    <fieldset style="border:0; padding:0; margin:0 0 16px" data-metadata-category="{{ $category }}">
      <legend class="form-label">{{ $label }}</legend>
      <div class="checkbox-group-grid">
        @forelse($metadataTags->get($category, collect()) as $tag)
          <label class="checkbox-pill">
            <input type="checkbox" name="recommendation_tag_ids[]" value="{{ $tag->id }}" @checked(in_array($tag->id, $selectedMetadataIds))>
            <span>{{ $tag->name }}</span>
          </label>
        @empty
          <span class="form-hint">Chưa có lựa chọn.</span>
        @endforelse
      </div>
    </fieldset>
  @endforeach
  @foreach($errors->get('recommendation_tag_ids*') as $messages)
    @foreach($messages as $message)
      <span class="invalid-feedback" style="display:block">{{ $message }}</span>
    @endforeach
  @endforeach
</div>
