<?php

use App\Models\Comic;
use App\Models\Genre;
use App\Models\Tag;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../laravel-blade/vendor/autoload.php';
$app = require __DIR__.'/../../laravel-blade/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.default') !== 'sqlite'
    || ! str_contains(config('database.connections.sqlite.database'), 'comicx-chatbot-e2e-')
    || config('ai.max_calls') !== 0) {
    throw new RuntimeException('Chatbot fixtures require isolated SQLite and disabled AI calls.');
}
(new RecommendationTaxonomySeeder)->run();
$genre = Genre::create(['name' => 'Fantasy']);
foreach (['a' => ['System', 'Overpowered MC'], 'b' => ['Harem']] as $suffix => $tags) {
    $comic = Comic::factory()->create([
        'title' => 'Chatbot Comic '.strtoupper($suffix), 'slug' => 'chatbot-comic-'.$suffix,
        'status' => 'ongoing', 'published_at' => now()->subDay(),
        'cover_image' => 'http://127.0.0.1:8765/images/default-brand.svg',
    ]);
    $comic->genres()->attach($genre->id);
    $comic->tags()->attach(Tag::whereIn('name', $tags)->pluck('id'));
}
