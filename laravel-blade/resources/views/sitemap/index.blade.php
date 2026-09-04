{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>{{ route('home') }}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
  <url><loc>{{ route('genres') }}</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
  <url><loc>{{ route('schedule') }}</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
  <url><loc>{{ route('schedule.completed') }}</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>
  <url><loc>{{ route('originals') }}</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
  <url><loc>{{ route('pages.about') }}</loc><changefreq>monthly</changefreq><priority>0.4</priority></url>
  <url><loc>{{ route('pages.contact') }}</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <url><loc>{{ route('pages.terms') }}</loc><changefreq>yearly</changefreq><priority>0.2</priority></url>
  <url><loc>{{ route('pages.privacy') }}</loc><changefreq>yearly</changefreq><priority>0.2</priority></url>
@foreach($genres as $genre)
  <url>
    <loc>{{ route('genres', ['genre' => $genre->slug]) }}</loc>
    <lastmod>{{ optional($genre->updated_at)->toAtomString() }}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
@endforeach
@foreach($tags as $tag)
  <url>
    <loc>{{ route('tags.show', $tag->slug) }}</loc>
    <lastmod>{{ optional($tag->updated_at)->toAtomString() }}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>
@endforeach
@foreach($comics as $comic)
  <url>
    <loc>{{ route('comics.show', $comic->slug) }}</loc>
    <lastmod>{{ optional($comic->updated_at)->toAtomString() }}</lastmod>
    <changefreq>daily</changefreq>
    <priority>0.8</priority>
  </url>
@endforeach
</urlset>
