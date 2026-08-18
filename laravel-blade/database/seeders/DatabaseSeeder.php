<?php
// database/seeders/DatabaseSeeder.php
// Seed dữ liệu mẫu lấy trực tiếp từ giao diện HTML

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Tag;
use App\Models\Chapter;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. GENRES ──────────────────────────────────────────────────
        $genres = collect([
            ['name' => 'Action',      'icon' => '⚔️'],
            ['name' => 'Fantasy',     'icon' => '🔮'],
            ['name' => 'Romance',     'icon' => '💜'],
            ['name' => 'Drama',       'icon' => '🎭'],
            ['name' => 'Comedy',      'icon' => '😂'],
            ['name' => 'Horror',      'icon' => '👻'],
            ['name' => 'Supernatural','icon' => '🌙'],
            ['name' => 'Mystery',     'icon' => '🔍'],
            ['name' => 'Mythology',   'icon' => '⚡'],
            ['name' => 'Isekai',      'icon' => '🌀'],
            ['name' => 'Superhero',   'icon' => '🦸'],
        ])->mapWithKeys(fn($g) => [
            $g['name'] => Genre::create(['name' => $g['name'], 'icon' => $g['icon']])
        ]);

        // ── 2. TAGS ────────────────────────────────────────────────────
        $tags = collect([
            ['name' => 'HOT',         'color' => '#FF5E36'],
            ['name' => 'POPULAR',     'color' => '#10B981'],
            ['name' => 'ORIGINAL',    'color' => '#8B5CF6'],
            ['name' => 'EDITOR_PICK', 'color' => '#F59E0B'],
            ['name' => 'NEW',         'color' => '#3B82F6'],
        ])->mapWithKeys(fn($t) => [
            $t['name'] => Tag::create(['name' => $t['name'], 'color' => $t['color']])
        ]);

        // ── 3. AUTHORS ─────────────────────────────────────────────────
        $authors = collect([
            'Chugong', 'REDICE Studio', 'SIU', 'singNsong', 'Sleepy-C',
            'Rachel Smythe', 'Koyoharu Gotouge', 'Gege Akutami',
            'Tatsuya Endo', 'Tatsuki Fujimoto', 'TurtleMe', 'Fuyuki23',
            'uru-chan', 'Son Jae-Ho', 'ZHENA',
        ])->mapWithKeys(fn($name) => [
            $name => Author::create(['name' => $name])
        ]);

        // ── 4. COMICS ─────────────────────────────────────────────────
        $comicsData = [
            [
                'title'         => 'Solo Leveling',
                'cover_image'   => 'https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg',
                'description'   => 'In a world where hunters fight deadly monsters, Sung Jin-Woo is the weakest of them all.',
                'status'        => 'completed',
                'is_original'   => true,
                'is_featured'   => true,
                'views'         => 12_800_000,
                'avg_rating'    => 9.9,
                'trending_rank' => 1,
                'genres'        => ['Action' => true, 'Fantasy' => false],
                'tags'          => ['HOT', 'ORIGINAL', 'EDITOR_PICK'],
                'authors'       => ['Chugong' => 'story', 'REDICE Studio' => 'art'],
                'chapters'      => [200],
                'schedule_days' => [4], // Thursday
            ],
            [
                'title'         => 'Tower of God',
                'cover_image'   => 'https://upload.wikimedia.org/wikipedia/en/7/7d/Tower_of_God_Volume_1_Cover.jpg',
                'description'   => 'Climb the mysterious tower to reach whatever your heart desires.',
                'status'        => 'ongoing',
                'is_original'   => true,
                'views'         => 8_600_000,
                'avg_rating'    => 9.8,
                'trending_rank' => 2,
                'genres'        => ['Fantasy' => true, 'Mystery' => false],
                'tags'          => ['HOT', 'ORIGINAL'],
                'authors'       => ['SIU' => 'both'],
                'chapters'      => [590],
                'schedule_days' => [4], // Thursday
            ],
            [
                'title'         => "Omniscient Reader's Viewpoint",
                'cover_image'   => 'https://upload.wikimedia.org/wikipedia/en/6/69/Omniscient_Reader%27s_Viewpoint_Volume_1_Cover.jpg',
                'description'   => 'He was the sole reader of an obscure novel, until the novel became reality.',
                'status'        => 'ongoing',
                'is_original'   => true,
                'views'         => 7_900_000,
                'avg_rating'    => 9.8,
                'trending_rank' => 3,
                'genres'        => ['Action' => true, 'Fantasy' => false],
                'tags'          => ['HOT', 'ORIGINAL'],
                'authors'       => ['singNsong' => 'story', 'Sleepy-C' => 'art'],
                'chapters'      => [185],
                'schedule_days' => [4], // Thursday
            ],
            [
                'title'         => 'Lore Olympus',
                'cover_image'   => 'https://upload.wikimedia.org/wikipedia/en/7/72/Lore_Olympus_Banner_Art.png',
                'description'   => 'A modern stylish retelling of the myth of Hades and Persephone.',
                'status'        => 'ongoing',
                'is_original'   => true,
                'views'         => 6_400_000,
                'avg_rating'    => 9.7,
                'trending_rank' => 4,
                'genres'        => ['Romance' => true, 'Mythology' => false],
                'tags'          => ['POPULAR', 'ORIGINAL'],
                'authors'       => ['Rachel Smythe' => 'both'],
                'chapters'      => [240],
                'schedule_days' => [4], // Thursday
            ],
            [
                'title'         => 'Demon Slayer: Kimetsu no Yaiba',
                'cover_image'   => 'https://upload.wikimedia.org/wikipedia/en/0/09/Demon_Slayer_-_Kimetsu_no_Yaiba%2C_volume_1.jpg',
                'description'   => 'A young boy fights demons to save his sister and avenge his family.',
                'status'        => 'completed',
                'is_original'   => true,
                'views'         => 9_400_000,
                'avg_rating'    => 9.8,
                'trending_rank' => 5,
                'genres'        => ['Action' => true, 'Supernatural' => false],
                'tags'          => ['HOT', 'ORIGINAL'],
                'authors'       => ['Koyoharu Gotouge' => 'both'],
                'chapters'      => [205],
                'schedule_days' => [4], // Thursday
            ],
            [
                'title'         => 'Jujutsu Kaisen',
                'cover_image'   => 'https://upload.wikimedia.org/wikipedia/en/4/46/Jujutsu_kaisen.jpg',
                'description'   => 'A high school student joins a secret organization of sorcerers to defeat curses.',
                'status'        => 'ongoing',
                'is_original'   => true,
                'views'         => 8_100_000,
                'avg_rating'    => 9.7,
                'trending_rank' => 6,
                'genres'        => ['Action' => true, 'Fantasy' => false],
                'tags'          => ['HOT', 'ORIGINAL'],
                'authors'       => ['Gege Akutami' => 'both'],
                'chapters'      => [254],
                'schedule_days' => [4], // Thursday
            ],
            [
                'title'         => 'Spy × Family',
                'cover_image'   => 'https://upload.wikimedia.org/wikipedia/en/5/51/Spy_Family_vol_1.jpg',
                'description'   => 'A master spy builds a fake family with an assassin wife and a telepath daughter.',
                'status'        => 'ongoing',
                'is_original'   => true,
                'views'         => 5_500_000,
                'avg_rating'    => 9.8,
                'trending_rank' => 7,
                'genres'        => ['Comedy' => true, 'Action' => false],
                'tags'          => ['POPULAR', 'ORIGINAL'],
                'authors'       => ['Tatsuya Endo' => 'both'],
                'chapters'      => [96],
                'schedule_days' => [1], // Monday
            ],
            [
                'title'         => 'Chainsaw Man',
                'cover_image'   => 'https://upload.wikimedia.org/wikipedia/en/2/24/Chainsawman.jpg',
                'description'   => 'A young man merges with his pet devil to become the ultimate devil hunter.',
                'status'        => 'ongoing',
                'is_original'   => true,
                'views'         => 7_000_000,
                'avg_rating'    => 9.6,
                'trending_rank' => 8,
                'genres'        => ['Action' => true, 'Horror' => false],
                'tags'          => ['HOT', 'ORIGINAL'],
                'authors'       => ['Tatsuki Fujimoto' => 'both'],
                'chapters'      => [160],
                'schedule_days' => [2], // Tuesday
            ],
            [
                'title'         => 'The Beginning After The End',
                'cover_image'   => 'https://upload.wikimedia.org/wikipedia/en/8/87/The_Beginning_After_The_End_vol_1.jpg',
                'description'   => 'Reincarnated into a magical world, a former king seeks to correct his past mistakes.',
                'status'        => 'ongoing',
                'is_original'   => false,
                'views'         => 7_200_000,
                'avg_rating'    => 9.9,
                'trending_rank' => null,
                'genres'        => ['Fantasy' => true, 'Isekai' => false],
                'tags'          => ['HOT'],
                'authors'       => ['TurtleMe' => 'story', 'Fuyuki23' => 'art'],
                'chapters'      => [175],
                'schedule_days' => [4], // Thursday
            ],
            [
                'title'         => 'unOrdinary',
                'cover_image'   => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600',
                'description'   => 'Nobody pays much attention to John—a normal teenager at a high school where superpowers rule.',
                'status'        => 'ongoing',
                'is_original'   => true,
                'views'         => 6_800_000,
                'avg_rating'    => 9.7,
                'trending_rank' => null,
                'genres'        => ['Drama' => true, 'Superhero' => false],
                'tags'          => ['POPULAR', 'ORIGINAL'],
                'authors'       => ['uru-chan' => 'both'],
                'chapters'      => [310],
                'schedule_days' => [4], // Thursday
            ],
            [
                'title'         => 'Eleceed',
                'cover_image'   => 'https://images.unsplash.com/photo-1569701812189-8093130ae7f2?w=600',
                'description'   => 'A cat-loving high schooler with super speed joins forces with an injured secret agent cat.',
                'status'        => 'ongoing',
                'is_original'   => true,
                'views'         => 8_300_000,
                'avg_rating'    => 9.9,
                'trending_rank' => null,
                'genres'        => ['Action' => true, 'Comedy' => false],
                'tags'          => ['HOT', 'ORIGINAL'],
                'authors'       => ['Son Jae-Ho' => 'story', 'ZHENA' => 'art'],
                'chapters'      => [280],
                'schedule_days' => [4], // Thursday
            ],
        ];

        foreach ($comicsData as $data) {
            $comic = Comic::create([
                'title'         => $data['title'],
                'cover_image'   => $data['cover_image'],
                'description'   => $data['description'],
                'status'        => $data['status'],
                'is_original'   => $data['is_original'],
                'is_featured'   => $data['is_featured'] ?? false,
                'views'         => $data['views'],
                'avg_rating'    => $data['avg_rating'],
                'trending_rank' => $data['trending_rank'],
            ]);

            // Gán thể loại (với is_primary)
            foreach ($data['genres'] as $genreName => $isPrimary) {
                if (isset($genres[$genreName])) {
                    $comic->genres()->attach($genres[$genreName]->id, ['is_primary' => $isPrimary]);
                }
            }

            // Gán tag
            foreach ($data['tags'] as $tagName) {
                if (isset($tags[$tagName])) {
                    $comic->tags()->attach($tags[$tagName]->id);
                }
            }

            // Gán tác giả (với role)
            foreach ($data['authors'] as $authorName => $role) {
                if (isset($authors[$authorName])) {
                    $comic->authors()->attach($authors[$authorName]->id, ['role' => $role]);
                }
            }

            // Tạo chapter mới nhất
            foreach ($data['chapters'] as $chapterNum) {
                Chapter::create([
                    'comic_id'       => $comic->id,
                    'chapter_number' => $chapterNum,
                    'published_at'   => now()->subHours(rand(1, 48)),
                ]);
            }

            // Tạo lịch ra tập
            foreach ($data['schedule_days'] as $day) {
                Schedule::create([
                    'comic_id'    => $comic->id,
                    'day_of_week' => $day,
                    'is_active'   => true,
                ]);
            }
        }

        $this->command->info('✅ Seeded ' . count($comicsData) . ' comics with genres, tags, authors, chapters & schedules!');
    }
}
