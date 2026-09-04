@php
  $seoDescription = Str::limit(trim(strip_tags((string) $comic->description)), 160);
  $seoImage = $comic->cover_image;
  $authorsForSeo = $comic->authors->pluck('name')->filter()->values()->all();
  $genresForSeo = $comic->genres->pluck('name')->filter()->values()->all();

  $comicSchema = [
      '@context' => 'https://schema.org',
      '@type' => 'CreativeWorkSeries',
      'name' => $comic->title,
      'url' => route('comics.show', $comic->slug),
      'description' => $seoDescription,
      'image' => $seoImage,
      'inLanguage' => 'vi',
      'genre' => $genresForSeo,
  ];

  if (!empty($authorsForSeo)) {
      $comicSchema['author'] = collect($authorsForSeo)->map(fn ($name) => [
          '@type' => 'Person',
          'name' => $name,
      ])->values()->all();
  }

  if (($comic->total_ratings ?? 0) > 0 && ($comic->avg_rating ?? 0) > 0) {
      $comicSchema['aggregateRating'] = [
          '@type' => 'AggregateRating',
          'ratingValue' => round((float) $comic->avg_rating, 1),
          'ratingCount' => (int) $comic->total_ratings,
          'bestRating' => 5,
          'worstRating' => 1,
      ];
  }
@endphp

<meta property="og:type" content="article" />
<meta property="og:title" content="{{ $comic->title }} - WebComics" />
<meta property="og:description" content="{{ $seoDescription }}" />
<meta property="og:image" content="{{ $seoImage }}" />
<meta property="og:url" content="{{ route('comics.show', $comic->slug) }}" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $comic->title }} - WebComics" />
<meta name="twitter:description" content="{{ $seoDescription }}" />
<meta name="twitter:image" content="{{ $seoImage }}" />
<script type="application/ld+json">{!! json_encode($comicSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
