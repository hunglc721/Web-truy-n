<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Banner;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\ComicLike;
use App\Models\Comment;
use App\Models\Genre;
use App\Models\Library;
use App\Models\Permission;
use App\Models\Rating;
use App\Models\ReadingHistory;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────────────────────
        // 1. ROLES & PERMISSIONS
        // ─────────────────────────────────────────────────────────────
        $permissionsList = [
            'dashboard.view'     => 'Xem trang tổng quan admin',
            'analytics.view'     => 'Xem báo cáo thống kê',
            'comics.view'        => 'Xem danh sách truyện',
            'comics.create'      => 'Tạo bộ truyện mới',
            'comics.update'      => 'Chỉnh sửa bộ truyện',
            'comics.delete'      => 'Xóa bộ truyện',
            'chapters.view'      => 'Xem danh sách chapter',
            'chapters.create'    => 'Đăng chapter mới',
            'chapters.update'    => 'Sửa chapter',
            'chapters.delete'    => 'Xóa chapter',
            'genres.manage'      => 'Quản lý thể loại',
            'tags.manage'        => 'Quản lý nhãn',
            'authors.manage'     => 'Quản lý tác giả',
            'users.view'         => 'Xem danh sách thành viên',
            'users.manage_role'  => 'Đổi quyền thành viên',
            'users.ban'          => 'Khóa/mở khóa tài khoản',
            'comments.view'      => 'Xem danh sách bình luận',
            'comments.moderate'  => 'Kiểm duyệt/ẩn/xóa bình luận',
            'reports.view'       => 'Xem báo cáo lỗi',
            'reports.manage'     => 'Xử lý báo cáo lỗi',
            'schedules.manage'   => 'Quản lý lịch ra truyện',
            'banners.manage'     => 'Quản lý banner quảng cáo',
            'audit.view'         => 'Xem nhật ký hoạt động hệ thống',
            'permissions.manage' => 'Phân quyền nâng cao',
            'settings.manage'    => 'Quản lý cấu hình website',
        ];

        $permissionModels = [];
        foreach ($permissionsList as $slug => $desc) {
            $permissionModels[$slug] = Permission::firstOrCreate(['slug' => $slug], ['name' => $desc]);
        }

        $roleAdmin = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Quản trị viên (Admin)']);
        $roleEditor = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Biên tập viên (Editor)']);
        $roleModerator = Role::firstOrCreate(['slug' => 'moderator'], ['name' => 'Kiểm duyệt viên (Moderator)']);
        $roleMember = Role::firstOrCreate(['slug' => 'member'], ['name' => 'Thành viên (Member)']);

        // Gán quyền cho roles
        $roleAdmin->permissions()->sync(array_values(array_map(fn($p) => $p->id, $permissionModels)));
        
        $editorPerms = ['dashboard.view', 'comics.view', 'comics.create', 'comics.update', 'chapters.view', 'chapters.create', 'chapters.update', 'genres.manage', 'tags.manage', 'authors.manage', 'schedules.manage', 'banners.manage'];
        $editorIds = [];
        foreach ($editorPerms as $ep) {
            if (isset($permissionModels[$ep])) {
                $editorIds[] = $permissionModels[$ep]->id;
            }
        }
        $roleEditor->permissions()->sync($editorIds);

        $modPerms = ['dashboard.view', 'comments.view', 'comments.moderate', 'reports.view', 'reports.manage', 'users.view'];
        $modIds = [];
        foreach ($modPerms as $mp) {
            if (isset($permissionModels[$mp])) {
                $modIds[] = $permissionModels[$mp]->id;
            }
        }
        $roleModerator->permissions()->sync($modIds);

        // ─────────────────────────────────────────────────────────────
        // 2. USERS
        // ─────────────────────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@webcomics.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('12345678'),
                'is_admin' => true,
                'role_id'  => $roleAdmin->id,
            ]
        );

        $editor = User::firstOrCreate(
            ['email' => 'editor@webcomics.com'],
            [
                'name'     => 'Biên Tập Viên Hoàng',
                'password' => Hash::make('12345678'),
                'is_admin' => false,
                'role_id'  => $roleEditor->id,
            ]
        );

        $mod = User::firstOrCreate(
            ['email' => 'mod@webcomics.com'],
            [
                'name'     => 'Mod Kiểm Duyệt',
                'password' => Hash::make('12345678'),
                'is_admin' => false,
                'role_id'  => $roleModerator->id,
            ]
        );

        $sampleUsers = [
            ['name' => 'Nguyễn Minh Tuấn', 'email' => 'user@webcomics.com'],
            ['name' => 'Trần Thu Hà',     'email' => 'ha.tran@example.com'],
            ['name' => 'Lê Quang Huy',    'email' => 'huy.le@example.com'],
            ['name' => 'Phạm Bảo Ngọc',   'email' => 'ngoc.pham@example.com'],
            ['name' => 'Vũ Đình Phong',   'email' => 'phong.vu@example.com'],
            ['name' => 'Đỗ Phương Linh',  'email' => 'linh.do@example.com'],
            ['name' => 'Bùi Hoàng Nam',   'email' => 'nam.bui@example.com'],
            ['name' => 'Mai Thanh Thảo',  'email' => 'thao.mai@example.com'],
        ];

        $users = [$admin, $editor, $mod];
        foreach ($sampleUsers as $u) {
            $users[] = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name'     => $u['name'],
                    'password' => Hash::make('12345678'),
                    'is_admin' => false,
                    'role_id'  => $roleMember->id,
                ]
            );
        }

        // ─────────────────────────────────────────────────────────────
        // 3. GENRES
        // ─────────────────────────────────────────────────────────────
        $genresData = [
            ['name' => 'Action',        'slug' => 'action',        'icon' => '⚔️'],
            ['name' => 'Fantasy',       'slug' => 'fantasy',       'icon' => '🔮'],
            ['name' => 'Romance',       'slug' => 'romance',       'icon' => '💜'],
            ['name' => 'Drama',         'slug' => 'drama',         'icon' => '🎭'],
            ['name' => 'Comedy',        'slug' => 'comedy',        'icon' => '😂'],
            ['name' => 'Horror',        'slug' => 'horror',        'icon' => '👻'],
            ['name' => 'Supernatural',  'slug' => 'supernatural',  'icon' => '🌙'],
            ['name' => 'Mystery',       'slug' => 'mystery',       'icon' => '🔍'],
            ['name' => 'Mythology',     'slug' => 'mythology',     'icon' => '⚡'],
            ['name' => 'Isekai',        'slug' => 'isekai',        'icon' => '🌀'],
            ['name' => 'Superhero',     'slug' => 'superhero',     'icon' => '🦸'],
            ['name' => 'Martial Arts',  'slug' => 'martial-arts',  'icon' => '🥋'],
            ['name' => 'Sci-Fi',        'slug' => 'sci-fi',        'icon' => '🚀'],
            ['name' => 'School Life',   'slug' => 'school-life',   'icon' => '🏫'],
            ['name' => 'Slice of Life', 'slug' => 'slice-of-life', 'icon' => '🍃'],
            ['name' => 'Adventure',     'slug' => 'adventure',     'icon' => '🗺️'],
        ];

        $genres = [];
        foreach ($genresData as $g) {
            $genres[$g['name']] = Genre::firstOrCreate(['slug' => $g['slug']], ['name' => $g['name'], 'icon' => $g['icon']]);
        }

        // ─────────────────────────────────────────────────────────────
        // 4. TAGS
        // ─────────────────────────────────────────────────────────────
        $tagsData = [
            ['name' => 'HOT',         'slug' => 'hot',         'color' => '#FF5E36'],
            ['name' => 'POPULAR',     'slug' => 'popular',     'color' => '#10B981'],
            ['name' => 'ORIGINAL',    'slug' => 'original',    'color' => '#8B5CF6'],
            ['name' => 'EDITOR_PICK', 'slug' => 'editor_pick', 'color' => '#F59E0B'],
            ['name' => 'NEW',         'slug' => 'new',         'color' => '#3B82F6'],
            ['name' => 'TRENDING',    'slug' => 'trending',    'color' => '#EC4899'],
        ];

        $tags = [];
        foreach ($tagsData as $t) {
            $tags[$t['name']] = Tag::firstOrCreate(['slug' => $t['slug']], ['name' => $t['name'], 'color' => $t['color']]);
        }

        // ─────────────────────────────────────────────────────────────
        // 5. AUTHORS
        // ─────────────────────────────────────────────────────────────
        $authorNames = [
            'Chugong', 'REDICE Studio', 'SIU', 'singNsong', 'Sleepy-C',
            'Rachel Smythe', 'Koyoharu Gotouge', 'Gege Akutami', 'Tatsuya Endo',
            'Tatsuki Fujimoto', 'TurtleMe', 'Fuyuki23', 'uru-chan', 'Son Jae-Ho',
            'ZHENA', 'Eiichiro Oda', 'Masashi Kishimoto', 'Tite Kubo',
            'Hajime Isayama', 'ONE', 'Yusuke Murata', 'Kentaro Miura',
            'Naoki Urasawa', 'Yaongyi', 'Spoon', 'BK_Moon', 'SanCheon',
            'Jeong Gwa-Jin', 'Park Tae-Jun', 'Taejun Pak Studio', 'Munseong Kim'
        ];

        $authors = [];
        foreach ($authorNames as $name) {
            $authors[$name] = Author::firstOrCreate(['name' => $name]);
        }

        // ─────────────────────────────────────────────────────────────
        // 6. SAMPLE COMICS LIST (>35 bộ truyện đầy đủ mọi thể loại)
        // ─────────────────────────────────────────────────────────────
        $comicsList = [
            // ── THỂ LOẠI: ACTION / FANTASY / HỆ THỐNG / MANHWA ──
            [
                'title'         => 'Solo Leveling (Tôi Thăng Cấp Một Mình)',
                'slug'          => 'solo-leveling',
                'cover_image'   => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Trong một thế giới nơi các Thợ Săn chiến đấu với quái vật từ hầm ngục bí ẩn, Sung Jin-Woo là Thợ Săn yếu nhất hạng E. Sau khi đối mặt với cái chết trong một hầm ngục kép, anh nhận được một nhiệm vụ bí mật và trở thành người duy nhất có khả năng thăng cấp không giới hạn!',
                'status'        => 'completed',
                'is_original'   => true,
                'is_featured'   => true,
                'views'         => 15_420_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 12450,
                'trending_rank' => 1,
                'genres'        => ['Action' => true, 'Fantasy' => false, 'Supernatural' => false],
                'tags'          => ['HOT', 'ORIGINAL', 'EDITOR_PICK'],
                'authors'       => ['Chugong' => 'story', 'REDICE Studio' => 'art'],
                'schedule_days' => [1, 4],
                'chapters_count'=> 8,
            ],
            [
                'title'         => 'Omniscient Reader’s Viewpoint (Toàn Trí Độc Giả)',
                'slug'          => 'omniscient-readers-viewpoint',
                'cover_image'   => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Kim Dokja là một nhân viên văn phòng bình thường, người duy nhất đọc đến chương cuối cùng của bộ tiểu thuyết mạng kéo dài 10 năm "Cách sống sót trong một thế giới diệt vong". Khi chương cuối cùng được đăng tải, thế giới thực bỗng chốc biến thành chính cuốn tiểu thuyết đó!',
                'status'        => 'ongoing',
                'is_original'   => true,
                'is_featured'   => true,
                'views'         => 12_850_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 9820,
                'trending_rank' => 2,
                'genres'        => ['Action' => true, 'Fantasy' => false, 'Mystery' => false],
                'tags'          => ['HOT', 'ORIGINAL', 'TRENDING'],
                'authors'       => ['singNsong' => 'story', 'Sleepy-C' => 'art'],
                'schedule_days' => [2, 5],
                'chapters_count'=> 6,
            ],
            [
                'title'         => 'The Beginning After The End (Điểm Bắt Đầu Sau Ngày Tận Thế)',
                'slug'          => 'the-beginning-after-the-end',
                'cover_image'   => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Vua Grey từng có sức mạnh, sự giàu có và danh vọng vô song trong một thế giới được cai trị bằng khả năng võ thuật. Tuy nhiên, sự cô độc luôn bám lấy ông. Được tái sinh vào một thế giới phép thuật mới lạ, ông có cơ hội thứ hai để sửa chữa sai lầm trong quá khứ.',
                'status'        => 'ongoing',
                'is_original'   => false,
                'is_featured'   => true,
                'views'         => 10_920_000,
                'avg_rating'    => 4.8,
                'total_ratings' => 8400,
                'trending_rank' => 3,
                'genres'        => ['Fantasy' => true, 'Isekai' => false, 'Adventure' => false],
                'tags'          => ['HOT', 'POPULAR'],
                'authors'       => ['TurtleMe' => 'story', 'Fuyuki23' => 'art'],
                'schedule_days' => [5],
                'chapters_count'=> 6,
            ],
            [
                'title'         => 'Eleceed (Siêu Năng & Mèo Béo)',
                'slug'          => 'eleceed',
                'cover_image'   => 'https://images.unsplash.com/photo-1569701812189-8093130ae7f2?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Jiwoo là một học sinh trung học tốt bụng có phản xạ nhanh như chớp. Một ngày nọ, cậu cứu một chú mèo mập mạp kỳ lạ, hóa ra lại là Kayden - một điệp viên thức tỉnh siêu năng lực mạnh nhất thế giới đang phải ẩn mình trong hình hài chú mèo!',
                'status'        => 'ongoing',
                'is_original'   => true,
                'is_featured'   => false,
                'views'         => 9_640_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 7650,
                'trending_rank' => 4,
                'genres'        => ['Action' => true, 'Comedy' => false, 'Superhero' => false],
                'tags'          => ['HOT', 'ORIGINAL'],
                'authors'       => ['Son Jae-Ho' => 'story', 'ZHENA' => 'art'],
                'schedule_days' => [3],
                'chapters_count'=> 5,
            ],
            [
                'title'         => 'Nano Machine (Nano Ma Thần)',
                'slug'          => 'nano-machine',
                'cover_image'   => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Cheon Yeo-Woon là con trai ngoài giá thú của Ma Giáo Giáo Chủ, luôn bị các gia tộc lớn tìm cách hãm hại. Khi đối mặt bờ vực cái chết, một hậu duệ đến từ tương lai xuất hiện và cấy vào cơ thể cậu cỗ máy Nano Machine công nghệ tối thượng!',
                'status'        => 'ongoing',
                'is_original'   => false,
                'is_featured'   => false,
                'views'         => 8_750_000,
                'avg_rating'    => 4.8,
                'total_ratings' => 6520,
                'trending_rank' => 5,
                'genres'        => ['Martial Arts' => true, 'Sci-Fi' => false, 'Action' => false],
                'tags'          => ['HOT', 'TRENDING'],
                'authors'       => ['SanCheon' => 'story', 'Jeong Gwa-Jin' => 'art'],
                'schedule_days' => [4],
                'chapters_count'=> 5,
            ],
            [
                'title'         => 'The Greatest Estate Developer (Bậc Thầy Thiết Kế Điền Trang)',
                'slug'          => 'the-greatest-estate-developer',
                'cover_image'   => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Một sinh viên ngành kỹ thuật xây dựng dân dụng xuyên không vào cuốn tiểu thuyết viễn tưởng trong thân xác của Lloyd Frontera - một thiếu gia quý tộc bất tài với khoản nợ khổng lồ. Bằng kiến thức kỹ thuật hiện đại, cậu bắt đầu cuộc cách mạng cải tạo đất đai và làm giàu ngoạn mục!',
                'status'        => 'ongoing',
                'is_original'   => true,
                'is_featured'   => true,
                'views'         => 8_200_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 7100,
                'trending_rank' => 6,
                'genres'        => ['Comedy' => true, 'Fantasy' => false, 'Isekai' => false],
                'tags'          => ['HOT', 'EDITOR_PICK'],
                'authors'       => ['BK_Moon' => 'story', 'Munseong Kim' => 'art'],
                'schedule_days' => [5],
                'chapters_count'=> 5,
            ],
            [
                'title'         => 'Tower of God (Tòa Tháp Của Thần)',
                'slug'          => 'tower-of-god',
                'cover_image'   => 'https://images.unsplash.com/photo-1514539079130-25950c84af65?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Hai mươi lăm Baam đã sống cả đời dưới bóng tối dưới đáy tòa tháp cùng người bạn duy nhất là Rachel. Khi Rachel bước vào Tòa Tháp để tìm kiếm bầu trời đầy sao, Baam quyết định leo tháp để tìm lại cô, mở ra những bí mật chấn động về thế giới.',
                'status'        => 'ongoing',
                'is_original'   => true,
                'is_featured'   => false,
                'views'         => 9_100_000,
                'avg_rating'    => 4.7,
                'total_ratings' => 5900,
                'trending_rank' => 7,
                'genres'        => ['Fantasy' => true, 'Mystery' => false, 'Adventure' => false],
                'tags'          => ['ORIGINAL', 'POPULAR'],
                'authors'       => ['SIU' => 'both'],
                'schedule_days' => [0],
                'chapters_count'=> 5,
            ],
            [
                'title'         => 'Lookism (Hoán Đổi Diệu Kỳ)',
                'slug'          => 'lookism',
                'cover_image'   => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Park Hyung Suk là một học sinh béo phì, xấu xí luôn bị bắt nạt tàn nhẫn tại trường. Sau khi chuyển đến trường mới, cậu tỉnh dậy và nhận ra mình sở hữu một cơ thể thứ hai: cực kỳ cao lớn, đẹp trai và hoàn hảo về thể lực.',
                'status'        => 'ongoing',
                'is_original'   => false,
                'is_featured'   => false,
                'views'         => 7_800_000,
                'avg_rating'    => 4.8,
                'total_ratings' => 5200,
                'trending_rank' => 8,
                'genres'        => ['Action' => true, 'Drama' => false, 'School Life' => false],
                'tags'          => ['HOT', 'POPULAR'],
                'authors'       => ['Park Tae-Jun' => 'both'],
                'schedule_days' => [5],
                'chapters_count'=> 4,
            ],
            [
                'title'         => 'Wind Breaker (Kỵ Sĩ Gió)',
                'slug'          => 'wind-breaker',
                'cover_image'   => 'https://images.unsplash.com/photo-1502680390469-be75c86b636f?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Jay là hội trưởng hội học sinh gương mẫu với áp lực học tập nặng nề. Niềm vui duy nhất của cậu là đạp xe một mình. Cuộc sống của cậu hoàn toàn thay đổi khi gia nhập câu lạc bộ đua xe đạp đường phố và tham gia giải đấu League of Street.',
                'status'        => 'ongoing',
                'is_original'   => false,
                'is_featured'   => false,
                'views'         => 6_950_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 4800,
                'trending_rank' => 9,
                'genres'        => ['Drama' => true, 'School Life' => false, 'Action' => false],
                'tags'          => ['POPULAR'],
                'authors'       => ['Taejun Pak Studio' => 'both'],
                'schedule_days' => [0],
                'chapters_count'=> 4,
            ],
            [
                'title'         => 'SSS-Class Revival Hunter (Thợ Săn Tự Sát Cấp SSS)',
                'slug'          => 'sss-class-revival-hunter',
                'cover_image'   => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Kim Gong-Ja sống cuộc đời ghen tị với các Thợ Săn hàng đầu. Cậu nhận được một kỹ năng cấp S: có thể sao chép kỹ năng của người đã giết mình khi cậu chết, kèm khả năng quay ngược thời gian 24 giờ trước khi chết!',
                'status'        => 'ongoing',
                'is_original'   => true,
                'is_featured'   => false,
                'views'         => 6_400_000,
                'avg_rating'    => 4.8,
                'total_ratings' => 4100,
                'trending_rank' => 10,
                'genres'        => ['Fantasy' => true, 'Action' => false, 'Supernatural' => false],
                'tags'          => ['ORIGINAL'],
                'authors'       => ['SIU' => 'both'],
                'schedule_days' => [6],
                'chapters_count'=> 4,
            ],

            // ── THỂ LOẠI: MANGA / SHOUNEN / SIÊU NHIÊN / DARK FANTASY ──
            [
                'title'         => 'Jujutsu Kaisen (Chú Thuật Hồi Chiến)',
                'slug'          => 'jujutsu-kaisen',
                'cover_image'   => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Yuji Itadori nuốt phải ngón tay bị nguyền rủa của Ryomen Sukuna - Vua Lời Nguyền huyền thoại. Cậu gia nhập Trường Chú thuật Tokyo để tìm kiếm các ngón tay còn lại và bảo vệ mọi người khỏi các nguyền hồn tàn ác.',
                'status'        => 'ongoing',
                'is_original'   => false,
                'is_featured'   => true,
                'views'         => 14_300_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 11200,
                'trending_rank' => null,
                'genres'        => ['Action' => true, 'Supernatural' => false, 'Horror' => false],
                'tags'          => ['HOT', 'POPULAR'],
                'authors'       => ['Gege Akutami' => 'both'],
                'schedule_days' => [6],
                'chapters_count'=> 5,
            ],
            [
                'title'         => 'Chainsaw Man (Người Cưa)',
                'slug'          => 'chainsaw-man',
                'cover_image'   => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Denji sống một cuộc đời nghèo khó bằng nghề săn quỷ cùng Pochita - Quỷ Cưa cưng. Bị phản bội và giết hại, Denji hợp nhất với Pochita và hồi sinh thành Chainsaw Man - sinh vật có lưỡi cưa mọc ra từ cơ thể!',
                'status'        => 'ongoing',
                'is_original'   => false,
                'is_featured'   => false,
                'views'         => 11_100_000,
                'avg_rating'    => 4.8,
                'total_ratings' => 9300,
                'trending_rank' => null,
                'genres'        => ['Action' => true, 'Horror' => false, 'Supernatural' => false],
                'tags'          => ['HOT'],
                'authors'       => ['Tatsuki Fujimoto' => 'both'],
                'schedule_days' => [2],
                'chapters_count'=> 4,
            ],
            [
                'title'         => 'Demon Slayer: Kimetsu no Yaiba (Thanh Gươm Diệt Quỷ)',
                'slug'          => 'demon-slayer-kimetsu-no-yaiba',
                'cover_image'   => 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Tanjiro Kamado trở về nhà sau một chuyến bán than và phát hiện toàn bộ gia đình bị quỷ tàn sát, chỉ còn em gái Nezuko sống sót nhưng đã biến thành quỷ. Cậu gia nhập Sát Quỷ Đội để tìm cách biến em gái trở lại thành người.',
                'status'        => 'completed',
                'is_original'   => false,
                'is_featured'   => true,
                'views'         => 16_800_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 15300,
                'trending_rank' => null,
                'genres'        => ['Action' => true, 'Supernatural' => false, 'Historical' => false],
                'tags'          => ['HOT', 'POPULAR', 'COMPLETED'],
                'authors'       => ['Koyoharu Gotouge' => 'both'],
                'schedule_days' => [0],
                'chapters_count'=> 5,
            ],
            [
                'title'         => 'Spy × Family (Gia Đình Điệp Viên)',
                'slug'          => 'spy-family',
                'cover_image'   => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Điệp viên Twilight phải thành lập một gia đình giả để thực hiện nhiệm vụ bảo vệ hòa bình thế giới. Không ngờ rằng người vợ anh chọn là sát thủ chuyên nghiệp, và cô con gái nuôi lại là một nhà ngoại cảm đọc được suy nghĩ!',
                'status'        => 'ongoing',
                'is_original'   => false,
                'is_featured'   => true,
                'views'         => 13_200_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 10400,
                'trending_rank' => null,
                'genres'        => ['Comedy' => true, 'Action' => false, 'Slice of Life' => false],
                'tags'          => ['POPULAR', 'EDITOR_PICK'],
                'authors'       => ['Tatsuya Endo' => 'both'],
                'schedule_days' => [0],
                'chapters_count'=> 4,
            ],
            [
                'title'         => 'One Piece (Đảo Hải Tặc)',
                'slug'          => 'one-piece',
                'cover_image'   => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Monkey D. Luffy cùng băng Mũ Rơm giương buồm ra khơi vượt qua Đại Hải Trình để tìm kiếm kho báu huyền thoại One Piece và trở thành Vua Hải Tặc tiếp theo.',
                'status'        => 'ongoing',
                'is_original'   => false,
                'is_featured'   => true,
                'views'         => 22_500_000,
                'avg_rating'    => 5.0,
                'total_ratings' => 21000,
                'trending_rank' => null,
                'genres'        => ['Adventure' => true, 'Action' => false, 'Comedy' => false],
                'tags'          => ['HOT', 'POPULAR', 'TRENDING'],
                'authors'       => ['Eiichiro Oda' => 'both'],
                'schedule_days' => [6],
                'chapters_count'=> 6,
            ],
            [
                'title'         => 'Attack on Titan (Đại Chiến Titan)',
                'slug'          => 'attack-on-titan',
                'cover_image'   => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Nhân loại sống ẩn mình sau ba bức tường thành khổng lồ để trốn tránh nỗi kinh hoàng từ những Titan ăn thịt người. Eren Yeager thề sẽ quét sạch mọi Titan sau khi chứng kiến mẹ mình bị ăn thịt.',
                'status'        => 'completed',
                'is_original'   => false,
                'is_featured'   => false,
                'views'         => 18_400_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 16500,
                'trending_rank' => null,
                'genres'        => ['Drama' => true, 'Action' => false, 'Mystery' => false],
                'tags'          => ['HOT', 'COMPLETED'],
                'authors'       => ['Hajime Isayama' => 'both'],
                'schedule_days' => [4],
                'chapters_count'=> 5,
            ],
            [
                'title'         => 'One Punch Man (Áo Choàng Hói)',
                'slug'          => 'one-punch-man',
                'cover_image'   => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Saitama là một anh hùng làm việc vì sở thích. Sau 3 năm rèn luyện khắc nghiệt, anh trở nên mạnh đến mức có thể hạ gục bất kỳ quái vật khủng khiếp nào chỉ bằng một cú đấm duy nhất!',
                'status'        => 'ongoing',
                'is_original'   => false,
                'is_featured'   => false,
                'views'         => 14_900_000,
                'avg_rating'    => 4.8,
                'total_ratings' => 12100,
                'trending_rank' => null,
                'genres'        => ['Action' => true, 'Comedy' => false, 'Superhero' => false],
                'tags'          => ['HOT', 'POPULAR'],
                'authors'       => ['ONE' => 'story', 'Yusuke Murata' => 'art'],
                'schedule_days' => [3],
                'chapters_count'=> 4,
            ],
            [
                'title'         => 'Berserk (Kiếm Sĩ Đen)',
                'slug'          => 'berserk',
                'cover_image'   => 'https://images.unsplash.com/photo-1569701812189-8093130ae7f2?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Guts - kiếm sĩ đơn độc mang theo thanh kiếm khổng lồ Dragon Slayer và cánh tay sắt, dấn thân vào hành trình đẫm máu để trả thù người bạn thân cũ Griffith trong một thế giới tăm tối đầy quỷ dữ.',
                'status'        => 'ongoing',
                'is_original'   => false,
                'is_featured'   => false,
                'views'         => 11_800_000,
                'avg_rating'    => 5.0,
                'total_ratings' => 9800,
                'trending_rank' => null,
                'genres'        => ['Horror' => true, 'Action' => false, 'Fantasy' => false],
                'tags'          => ['POPULAR'],
                'authors'       => ['Kentaro Miura' => 'both'],
                'schedule_days' => [5],
                'chapters_count'=> 4,
            ],
            [
                'title'         => 'Frieren: Beyond Journey’s End (Pháp Sư Frieren)',
                'slug'          => 'frieren-beyond-journeys-end',
                'cover_image'   => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Sau khi tiêu diệt Ma Vương và mang lại hòa bình, pháp sư elf Frieren sống qua hàng trăm năm và nhìn những người đồng đội cũ già đi và qua đời. Cô bắt đầu hành trình mới để thấu hiểu lòng người.',
                'status'        => 'ongoing',
                'is_original'   => false,
                'is_featured'   => true,
                'views'         => 9_800_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 8700,
                'trending_rank' => null,
                'genres'        => ['Fantasy' => true, 'Adventure' => false, 'Drama' => false],
                'tags'          => ['HOT', 'EDITOR_PICK'],
                'authors'       => ['singNsong' => 'story', 'Sleepy-C' => 'art'],
                'schedule_days' => [2],
                'chapters_count'=> 4,
            ],

            // ── THỂ LOẠI: ROMANCE / NGÔN TÌNH / TÁI SINH / DRAMA ──
            [
                'title'         => 'Lore Olympus (Thần Thoại Đỉnh Olympus)',
                'slug'          => 'lore-olympus',
                'cover_image'   => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Bản phóng tác hiện đại đầy màu sắc và nghệ thuật về câu chuyện tình yêu kinh điển giữa Thần Địa Ngục Hades và Nữ thần mùa xuân Persephone giữa những âm mưu gia tộc trên đỉnh Olympus.',
                'status'        => 'ongoing',
                'is_original'   => true,
                'is_featured'   => true,
                'views'         => 8_900_000,
                'avg_rating'    => 4.8,
                'total_ratings' => 7400,
                'trending_rank' => null,
                'genres'        => ['Romance' => true, 'Mythology' => false, 'Drama' => false],
                'tags'          => ['ORIGINAL', 'POPULAR'],
                'authors'       => ['Rachel Smythe' => 'both'],
                'schedule_days' => [0],
                'chapters_count'=> 5,
            ],
            [
                'title'         => 'True Beauty (Vẻ Đẹp Thực Sự)',
                'slug'          => 'true-beauty',
                'cover_image'   => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Im Jugyeong tự ti vì nhan sắc xấu xí đã thành thạo kỹ năng trang điểm kỳ diệu để biến thành "nữ thần" trường học. Liệu cô có thể giữ kín bí mật trước hai chàng trai nổi tiếng nhất trường?',
                'status'        => 'completed',
                'is_original'   => true,
                'is_featured'   => false,
                'views'         => 12_400_000,
                'avg_rating'    => 4.7,
                'total_ratings' => 9100,
                'trending_rank' => null,
                'genres'        => ['Romance' => true, 'School Life' => false, 'Drama' => false],
                'tags'          => ['POPULAR', 'COMPLETED'],
                'authors'       => ['Yaongyi' => 'both'],
                'schedule_days' => [3],
                'chapters_count'=> 4,
            ],
            [
                'title'         => 'Who Made Me a Princess (Một Ngày Nọ Tôi Trở Thành Công Chúa)',
                'slug'          => 'who-made-me-a-princess',
                'cover_image'   => 'https://images.unsplash.com/photo-1502680390469-be75c86b636f?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Tái sinh thành công chúa Athanasia đáng thương bị chính cha ruột - Hoàng đế bạo chúa Claude xử tử. Để sống sót, cô phải tìm mọi cách lấy lòng người cha lạnh lùng vô cảm này.',
                'status'        => 'completed',
                'is_original'   => true,
                'is_featured'   => true,
                'views'         => 11_300_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 10200,
                'trending_rank' => null,
                'genres'        => ['Fantasy' => true, 'Romance' => false, 'Drama' => false],
                'tags'          => ['HOT', 'COMPLETED', 'EDITOR_PICK'],
                'authors'       => ['Spoon' => 'both'],
                'schedule_days' => [1],
                'chapters_count'=> 4,
            ],
            [
                'title'         => 'The Remarried Empress (Hoàng Hậu Tái Hôn)',
                'slug'          => 'the-remarried-empress',
                'cover_image'   => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Navier là vị hoàng hậu hoàn hảo của Đế quốc phương Đông. Khi hoàng đế Sovieshu đòi ly hôn để phong tì thiếp Rashta lên làm hoàng hậu, Navier bình tĩnh chấp thuận với điều kiện: Tái hôn ngay lập tức với Vua Heinrey của Đế quốc phương Tây!',
                'status'        => 'ongoing',
                'is_original'   => true,
                'is_featured'   => false,
                'views'         => 9_500_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 7800,
                'trending_rank' => null,
                'genres'        => ['Drama' => true, 'Romance' => false, 'Fantasy' => false],
                'tags'          => ['HOT', 'ORIGINAL'],
                'authors'       => ['singNsong' => 'story', 'Sleepy-C' => 'art'],
                'schedule_days' => [6],
                'chapters_count'=> 4,
            ],
            [
                'title'         => 'Villains Are Destined to Die (Cái Chết Là Điểm Đến Duy Nhất Của Nữ Phụ)',
                'slug'          => 'villains-are-destined-to-die',
                'cover_image'   => 'https://images.unsplash.com/photo-1514539079130-25950c84af65?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Xuyên không vào chế độ khó của trò chơi hẹn hò otome trong vai nữ phản diện Penelope Eckhart - người bị định sẵn sẽ chết thảm ở mọi kết cục. Cô phải tìm cách sống sót giữa các nam chính nguy hiểm.',
                'status'        => 'ongoing',
                'is_original'   => true,
                'is_featured'   => false,
                'views'         => 7_900_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 6400,
                'trending_rank' => null,
                'genres'        => ['Isekai' => true, 'Romance' => false, 'Drama' => false],
                'tags'          => ['HOT'],
                'authors'       => ['Spoon' => 'both'],
                'schedule_days' => [4],
                'chapters_count'=> 4,
            ],

            // ── THỂ LOẠI: BÍ ẨN / KINH DỊ / TRINH THÁM ──
            [
                'title'         => 'Sweet Home (Thế Giới Ma Quái)',
                'slug'          => 'sweet-home',
                'cover_image'   => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Sau cái chết của cả gia đình, nam sinh Cha Hyun-soo sống cô độc trong một căn chung cư cũ nát. Một ngày nọ, con người bắt đầu biến thành những con quái vật đáng sợ phản chiếu dục vọng đen tối nhất của họ.',
                'status'        => 'completed',
                'is_original'   => true,
                'is_featured'   => false,
                'views'         => 10_500_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 8900,
                'trending_rank' => null,
                'genres'        => ['Horror' => true, 'Supernatural' => false, 'Mystery' => false],
                'tags'          => ['POPULAR', 'COMPLETED'],
                'authors'       => ['Chugong' => 'story', 'REDICE Studio' => 'art'],
                'schedule_days' => [2],
                'chapters_count'=> 4,
            ],
            [
                'title'         => 'Bastard (Kẻ Sát Nhân Trong Nhà)',
                'slug'          => 'bastard',
                'cover_image'   => 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Jin Seon sống với người cha thành đạt được xã hội kính trọng. Nhưng sau cánh cửa đóng kín, người cha đó lại là một kẻ giết người hàng loạt tâm thần, và Jin bị ép làm đồng phạm bất đắc dĩ.',
                'status'        => 'completed',
                'is_original'   => true,
                'is_featured'   => false,
                'views'         => 8_600_000,
                'avg_rating'    => 5.0,
                'total_ratings' => 7400,
                'trending_rank' => null,
                'genres'        => ['Mystery' => true, 'Horror' => false, 'Drama' => false],
                'tags'          => ['ORIGINAL', 'COMPLETED'],
                'authors'       => ['Chugong' => 'story', 'REDICE Studio' => 'art'],
                'schedule_days' => [5],
                'chapters_count'=> 4,
            ],
            [
                'title'         => 'Death Note (Cuốn Sổ Tử Thần)',
                'slug'          => 'death-note',
                'cover_image'   => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Light Yagami nhặt được cuốn sổ tử thần rơi từ tay Thần Chết Ryuk. Bất kỳ ai bị viết tên vào sổ sẽ chết. Light bắt đầu thanh trừng tội phạm dưới danh xưng Kira, mở ra trận đấu trí thế kỷ với thám tử thiên tài L.',
                'status'        => 'completed',
                'is_original'   => false,
                'is_featured'   => true,
                'views'         => 19_200_000,
                'avg_rating'    => 5.0,
                'total_ratings' => 18000,
                'trending_rank' => null,
                'genres'        => ['Mystery' => true, 'Supernatural' => false, 'Drama' => false],
                'tags'          => ['HOT', 'COMPLETED'],
                'authors'       => ['Naoki Urasawa' => 'both'],
                'schedule_days' => [0],
                'chapters_count'=> 5,
            ],
            [
                'title'         => 'Monster (Quái Vật)',
                'slug'          => 'monster',
                'cover_image'   => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Bác sĩ phẫu thuật thần kinh thiên tài Kenzo Tenma đã cứu sống một cậu bé bị bắn vào đầu thay vì Thị trưởng thành phố. Nhiều năm sau, cậu bé năm nào lớn lên thành một con quái vật giết người hàng loạt máu lạnh.',
                'status'        => 'completed',
                'is_original'   => false,
                'is_featured'   => false,
                'views'         => 7_400_000,
                'avg_rating'    => 5.0,
                'total_ratings' => 6100,
                'trending_rank' => null,
                'genres'        => ['Mystery' => true, 'Drama' => false],
                'tags'          => ['EDITOR_PICK', 'COMPLETED'],
                'authors'       => ['Naoki Urasawa' => 'both'],
                'schedule_days' => [3],
                'chapters_count'=> 4,
            ],

            // ── THỂ LOẠI: THỂ THAO / HỌC ĐƯỜNG ──
            [
                'title'         => 'Blue Lock (Dự Án Khóa Xanh)',
                'slug'          => 'blue-lock',
                'cover_image'   => 'https://images.unsplash.com/photo-1502680390469-be75c86b636f?w=600&auto=format&fit=crop&q=80',
                'description'   => '300 tiền đạo trẻ xuất sắc nhất Nhật Bản bị nhốt trong trung tâm huấn luyện khép kín Blue Lock để tìm ra một tiền đạo ích kỷ và vĩ đại nhất, người sẽ đưa Nhật Bản vô địch World Cup!',
                'status'        => 'ongoing',
                'is_original'   => false,
                'is_featured'   => true,
                'views'         => 11_500_000,
                'avg_rating'    => 4.8,
                'total_ratings' => 9100,
                'trending_rank' => null,
                'genres'        => ['Action' => true, 'School Life' => false, 'Drama' => false],
                'tags'          => ['HOT', 'TRENDING'],
                'authors'       => ['Tatsuya Endo' => 'both'],
                'schedule_days' => [2],
                'chapters_count'=> 4,
            ],
            [
                'title'         => 'Haikyuu!! (Vua Bóng Chuyền)',
                'slug'          => 'haikyuu',
                'cover_image'   => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=600&auto=format&fit=crop&q=80',
                'description'   => 'Shoyo Hinata tuy thấp bé nhưng có sức bật phi thường. Gia nhập trường trung học Karasuno, cậu gặp lại đối thủ truyền kiếp Kageyama Tobio và cùng nhau tạo nên cặp đôi chuyền - đập quái kiệt.',
                'status'        => 'completed',
                'is_original'   => false,
                'is_featured'   => false,
                'views'         => 13_800_000,
                'avg_rating'    => 4.9,
                'total_ratings' => 11400,
                'trending_rank' => null,
                'genres'        => ['Comedy' => true, 'School Life' => false, 'Drama' => false],
                'tags'          => ['POPULAR', 'COMPLETED'],
                'authors'       => ['Tatsuya Endo' => 'both'],
                'schedule_days' => [4],
                'chapters_count'=> 4,
            ],
        ];

        // Mẫu ảnh trang truyện chất lượng cao
        $samplePages = [
            'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=900&auto=format&fit=crop&q=85',
            'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=900&auto=format&fit=crop&q=85',
            'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=900&auto=format&fit=crop&q=85',
            'https://images.unsplash.com/photo-1563089145-599997674d42?w=900&auto=format&fit=crop&q=85',
            'https://images.unsplash.com/photo-1518770660439-4636190af475?w=900&auto=format&fit=crop&q=85',
            'https://images.unsplash.com/photo-1569701812189-8093130ae7f2?w=900&auto=format&fit=crop&q=85',
            'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=900&auto=format&fit=crop&q=85',
        ];

        $sampleReviews = [
            5 => ['Bộ truyện xuất sắc nhất từng đọc, nét vẽ đỉnh cao và cốt truyện cuốn hút!', 'Quá hay, art đẹp mê ly không thể rời mắt!', 'Cực phẩm! Xem đi xem lại vẫn thấy cảm xúc như lần đầu.', '10/10 không có điểm nào để chê!'],
            4 => ['Truyện rất cuốn, nhịp điệu nhanh và hấp dẫn.', 'Đọc rất giải trí, mong tác giả ra chương đều đặn hơn.', 'Nội dung thú vị, art càng về sau càng tiến bộ.'],
            3 => ['Truyện đọc tạm ổn, đoạn mở đầu hơi chậm.', 'Khá hay nhưng mong tác giả phát triển nhân vật phụ sâu hơn.'],
        ];

        $sampleComments = [
            'Truyện này đỉnh thật sự, hóng chương tiếp theo quá!',
            'Art bộ này càng ngày càng lên tay, cảnh combat mãn nhãn ghê.',
            'Có ai đọc truyện chữ chưa cho xin chút review với?',
            'Main ngầu đét, phong thái đúng chất vô địch.',
            'Mỗi tuần hóng từng chap mòn cả mắt, mong nhóm dịch ra nhanh hơn!',
            'Tình tiết đoạn này bất ngờ thật, không đoán trước được luôn.',
            'Đọc từ lúc mới ra chap 1 đến giờ vẫn thấy mê.',
            'Tác giả gài bẫy logic khéo léo ghê, 10 điểm!',
        ];

        foreach ($comicsList as $data) {
            $comic = Comic::create([
                'title'         => $data['title'],
                'slug'          => $data['slug'],
                'cover_image'   => $data['cover_image'],
                'description'   => $data['description'],
                'status'        => $data['status'],
                'is_original'   => $data['is_original'],
                'is_featured'   => $data['is_featured'] ?? false,
                'views'         => $data['views'],
                'avg_rating'    => $data['avg_rating'],
                'total_ratings' => $data['total_ratings'],
                'trending_rank' => $data['trending_rank'],
                'published_at'  => now()->subDays(rand(30, 300)),
            ]);

            // Gán thể loại
            foreach ($data['genres'] as $genreName => $isPrimary) {
                if (isset($genres[$genreName])) {
                    $comic->genres()->attach($genres[$genreName]->id, ['is_primary' => $isPrimary]);
                }
            }

            // Gán tags
            foreach ($data['tags'] as $tagName) {
                if (isset($tags[$tagName])) {
                    $comic->tags()->attach($tags[$tagName]->id);
                }
            }

            // Gán tác giả
            foreach ($data['authors'] as $authorName => $role) {
                if (isset($authors[$authorName])) {
                    $comic->authors()->attach($authors[$authorName]->id, ['role' => $role]);
                }
            }

            // Tạo các chapters
            $chapCount = $data['chapters_count'] ?? 4;
            $createdChapters = [];
            for ($c = 1; $c <= $chapCount; $c++) {
                $isFree = $c <= 2 || ($c % 3 !== 0);
                $chapter = Chapter::create([
                    'comic_id'          => $comic->id,
                    'chapter_number'    => $c,
                    'title'             => 'Chương ' . $c . ': ' . ($c === 1 ? 'Khởi đầu mới' : ($c === $chapCount ? 'Trận chiến quyết định' : 'Hành trình tiếp diễn')),
                    'slug'              => 'chapter-' . $c,
                    'pages'             => $samplePages,
                    'is_free'           => $isFree,
                    'processing_status' => 'ready',
                    'views'             => rand(5000, 150000),
                    'published_at'      => now()->subDays($chapCount - $c)->subHours(rand(1, 12)),
                ]);
                $createdChapters[] = $chapter;
            }

            // Tạo lịch ra tập
            foreach ($data['schedule_days'] as $day) {
                Schedule::create([
                    'comic_id'     => $comic->id,
                    'day_of_week'  => $day,
                    'release_time' => '20:00:00',
                    'is_active'    => true,
                ]);
            }

            // Tạo Đánh giá mẫu (Ratings)
            $ratingUsers = collect($users)->shuffle()->take(rand(4, 7));
            foreach ($ratingUsers as $u) {
                $score = rand(4, 5);
                $reviewText = $sampleReviews[$score][array_rand($sampleReviews[$score])];
                Rating::create([
                    'user_id'    => $u->id,
                    'comic_id'   => $comic->id,
                    'score'      => $score,
                    'review'     => rand(0, 1) ? $reviewText : null,
                    'created_at' => now()->subDays(rand(1, 20)),
                ]);
            }

            // Tạo Bình luận mẫu (Comments)
            $commentUsers = collect($users)->shuffle()->take(rand(3, 6));
            foreach ($commentUsers as $cu) {
                $comment = Comment::create([
                    'user_id'    => $cu->id,
                    'comic_id'   => $comic->id,
                    'chapter_id' => rand(0, 1) ? $createdChapters[array_rand($createdChapters)]->id : null,
                    'parent_id'  => null,
                    'content'    => $sampleComments[array_rand($sampleComments)],
                    'status'     => 'approved',
                    'likes_count'=> rand(0, 25),
                    'created_at' => now()->subDays(rand(1, 15)),
                ]);

                // Reply mẫu
                if (rand(0, 1)) {
                    $replyUser = collect($users)->where('id', '!=', $cu->id)->random();
                    Comment::create([
                        'user_id'    => $replyUser->id,
                        'comic_id'   => $comic->id,
                        'chapter_id' => $comment->chapter_id,
                        'parent_id'  => $comment->id,
                        'content'    => 'Đồng quan điểm với bạn luôn, đoạn này xem cực bánh cuốn!',
                        'status'     => 'approved',
                        'likes_count'=> rand(1, 10),
                        'created_at' => $comment->created_at->addMinutes(rand(5, 120)),
                    ]);
                }
            }

            // Tạo bookmark vào tủ sách & like cho admin & sample users
            Library::create([
                'user_id'    => $admin->id,
                'comic_id'   => $comic->id,
                'status'     => 'reading',
                'added_at'   => now()->subDays(rand(1, 10)),
            ]);

            ComicLike::create([
                'user_id'    => $admin->id,
                'comic_id'   => $comic->id,
                'liked_at'   => now()->subDays(rand(1, 10)),
            ]);

            // Lịch sử đọc mẫu
            if (!empty($createdChapters)) {
                $lastChap = $createdChapters[array_rand($createdChapters)];
                ReadingHistory::create([
                    'user_id'        => $admin->id,
                    'comic_id'       => $comic->id,
                    'chapter_id'     => $lastChap->id,
                    'scroll_percent' => rand(30, 85),
                    'last_read_at'   => now()->subHours(rand(1, 48)),
                ]);
            }
        }

        // ─────────────────────────────────────────────────────────────
        // 7. HERO SLIDER BANNERS
        // ─────────────────────────────────────────────────────────────
        $bannersData = [
            [
                'title'       => 'Solo Leveling — Trở lại hầm ngục kép & Thức tỉnh Ma Vương',
                'image_url'   => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=1400&auto=format&fit=crop&q=85',
                'link_url'    => '/truyen/solo-leveling',
                'order'       => 1,
                'is_active'   => true,
            ],
            [
                'title'       => 'Omniscient Reader’s Viewpoint — Kịch bản thế giới diệt vong',
                'image_url'   => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=1400&auto=format&fit=crop&q=85',
                'link_url'    => '/truyen/omniscient-readers-viewpoint',
                'order'       => 2,
                'is_active'   => true,
            ],
            [
                'title'       => 'One Piece — Đại chiến nghẹt thở tại Đảo Hải Tặc',
                'image_url'   => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1400&auto=format&fit=crop&q=85',
                'link_url'    => '/truyen/one-piece',
                'order'       => 3,
                'is_active'   => true,
            ],
            [
                'title'       => 'The Greatest Estate Developer — Siêu phẩm hài hước cải tạo điền trang',
                'image_url'   => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=1400&auto=format&fit=crop&q=85',
                'link_url'    => '/truyen/the-greatest-estate-developer',
                'order'       => 4,
                'is_active'   => true,
            ],
        ];

        foreach ($bannersData as $b) {
            Banner::create($b);
        }

        // ─────────────────────────────────────────────────────────────
        // 8. SITE SETTINGS
        // ─────────────────────────────────────────────────────────────
        $settings = [
            'site_name'        => 'WebComics',
            'site_tagline'     => 'Đọc Manga, Manhwa & Manhua Bản Quyền Miễn Phí',
            'meta_description' => 'Khám phá hàng ngàn bộ truyện tranh đặc sắc, cập nhật liên tục mỗi ngày với hình ảnh sắc nét và trải nghiệm đọc mượt mà.',
            'seo_keywords'     => 'đọc truyện, truyện tranh online, manga, manhwa, manhua, webtoon',
            'contact_email'    => 'support@webcomics.com',
            'maintenance_mode' => '0',
            'allow_registration'=> '1',
            'comments_require_approval' => '0',
        ];

        foreach ($settings as $k => $v) {
            Setting::putValue($k, $v);
        }

        $this->command->info('🎉 Đã seed thành công ' . count($comicsList) . ' bộ truyện đa thể loại, ' . count($genresData) . ' thể loại, người dùng, chapters, banners và bình luận mẫu!');
    }
}
