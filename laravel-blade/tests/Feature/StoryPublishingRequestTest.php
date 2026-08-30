<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StoryPublishingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoryPublishingRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Tạo hoặc lấy role admin và gán full quyền
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Quản trị viên']);
        $managePerm = Permission::firstOrCreate(
            ['slug' => 'story_requests.manage'],
            ['name' => 'Quản lý duyệt đơn đăng truyện']
        );
        $adminRole->permissions()->syncWithoutDetaching([$managePerm->id]);

        $this->adminUser = User::factory()->create([
            'email'    => 'admin_' . uniqid() . '@test.com',
            'is_admin' => true,
            'role_id'  => $adminRole->id,
        ]);

        $memberRole = Role::firstOrCreate(['slug' => 'member'], ['name' => 'Thành viên']);
        $this->regularUser = User::factory()->create([
            'email'    => 'member_' . uniqid() . '@test.com',
            'is_admin' => false,
            'role_id'  => $memberRole->id,
        ]);

        Genre::firstOrCreate(['slug' => 'hanh-dong'], ['name' => 'Hành Động']);
        Genre::firstOrCreate(['slug' => 'hai-huoc'], ['name' => 'Hài Hước']);
    }

    public function test_can_view_publish_form_as_guest_and_user(): void
    {
        $guestResponse = $this->get(route('publish.create'));
        $guestResponse->assertStatus(200);
        $guestResponse->assertSee('Đăng Ký Đăng Truyện');

        $userResponse = $this->actingAs($this->regularUser)->get(route('publish.create'));
        $userResponse->assertStatus(200);
        $userResponse->assertSee($this->regularUser->name);
    }

    public function test_publishing_form_validates_required_fields(): void
    {
        $response = $this->post(route('publish.store'), []);
        $response->assertSessionHasErrors([
            'creator_name',
            'email',
            'phone_or_social',
            'experience_level',
            'story_title',
            'story_type',
            'story_status',
            'summary',
            'terms_agreed',
        ]);
    }

    public function test_can_submit_story_request_successfully(): void
    {
        $coverImage = UploadedFile::fake()->image('cover.jpg', 600, 800);
        $sampleFile = UploadedFile::fake()->create('sample_manuscript.zip', 1024, 'application/zip');

        $payload = [
            'creator_name'         => 'Nguyễn Văn Tác Giả',
            'email'                => 'author@test.com',
            'phone_or_social'      => '0987654321',
            'team_name'            => 'Phoenix Translation Team',
            'experience_level'     => 'experienced',
            'story_title'          => 'Đại Quản Gia Là Ma Hoàng',
            'story_original_title' => 'Magic Emperor',
            'story_type'           => 'translation',
            'genres'               => ['Hành Động', 'Hài Hước'],
            'story_status'         => 'ongoing',
            'summary'              => 'Trác Nhất Phàm vì nắm giữ bí kíp Cửu U Ma Đế mà bị đệ tử phản bội, trọng sinh vào thân xác quản gia của Lạc Gia...',
            'sample_link'          => 'https://drive.google.com/drive/folders/sample-test',
            'cover_image'          => $coverImage,
            'sample_file'          => $sampleFile,
            'note'                 => 'Rất mong được hợp tác cùng ban biên tập WebComics.',
            'terms_agreed'         => '1',
        ];

        $response = $this->actingAs($this->regularUser)->post(route('publish.store'), $payload);

        $response->assertRedirect(route('user.publishingRequests'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('story_publishing_requests', [
            'user_id'          => $this->regularUser->id,
            'creator_name'     => 'Nguyễn Văn Tác Giả',
            'story_title'      => 'Đại Quản Gia Là Ma Hoàng',
            'story_type'       => 'translation',
            'experience_level' => 'experienced',
            'status'           => 'pending',
        ]);

        $createdRequest = StoryPublishingRequest::where('story_title', 'Đại Quản Gia Là Ma Hoàng')->first();
        $this->assertNotNull($createdRequest->cover_image_path);
        $this->assertNotNull($createdRequest->sample_file_path);
        Storage::disk('public')->assertExists($createdRequest->cover_image_path);
        Storage::disk('public')->assertExists($createdRequest->sample_file_path);
    }

    public function test_user_can_view_own_publishing_requests(): void
    {
        StoryPublishingRequest::create([
            'user_id'          => $this->regularUser->id,
            'creator_name'     => 'Test Author',
            'email'            => 'author@test.com',
            'phone_or_social'  => '0123456789',
            'experience_level' => 'beginner',
            'story_title'      => 'Truyện Của Tôi 1',
            'story_type'       => 'original',
            'genres'           => ['Hành Động'],
            'story_status'     => 'ongoing',
            'summary'          => 'Tóm tắt nội dung truyện cực kỳ hấp dẫn và lôi cuốn người đọc...',
            'status'           => 'pending',
        ]);

        $response = $this->actingAs($this->regularUser)->get(route('user.publishingRequests'));
        $response->assertStatus(200);
        $response->assertSee('Truyện Của Tôi 1');
        $response->assertSee('Chờ duyệt');
    }

    public function test_admin_can_view_story_requests_list_and_detail(): void
    {
        $req = StoryPublishingRequest::create([
            'user_id'          => $this->regularUser->id,
            'creator_name'     => 'Tác giả Sáng Tác',
            'email'            => 'creator@test.com',
            'phone_or_social'  => '0909090909',
            'experience_level' => 'professional',
            'story_title'      => 'Huyền Thoại Trọng Sinh',
            'story_type'       => 'original',
            'genres'           => ['Hành Động'],
            'story_status'     => 'completed',
            'summary'          => 'Một hành trình phiêu lưu xuyên không gian và thời gian kinh điển...',
            'status'           => 'pending',
        ]);

        $listResponse = $this->actingAs($this->adminUser)->get(route('admin.storyRequests.index'));
        $listResponse->assertStatus(200);
        $listResponse->assertSee('Huyền Thoại Trọng Sinh');
        $listResponse->assertSee('Tác giả Sáng Tác');

        $showResponse = $this->actingAs($this->adminUser)->get(route('admin.storyRequests.show', $req->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Thẩm Định Đơn Đăng Truyện #' . $req->id);
        $showResponse->assertSee('Huyền Thoại Trọng Sinh');
    }

    public function test_admin_can_update_status_and_user_receives_notification(): void
    {
        $req = StoryPublishingRequest::create([
            'user_id'          => $this->regularUser->id,
            'creator_name'     => 'Author A',
            'email'            => 'authorA@test.com',
            'phone_or_social'  => '0911223344',
            'experience_level' => 'experienced',
            'story_title'      => 'Tác Phẩm Được Duyệt',
            'story_type'       => 'original',
            'genres'           => ['Hành Động'],
            'story_status'     => 'ongoing',
            'summary'          => 'Tóm tắt nội dung tác phẩm để gửi admin duyệt kiểm tra...',
            'status'           => 'pending',
        ]);

        $response = $this->actingAs($this->adminUser)->patch(route('admin.storyRequests.updateStatus', $req->id), [
            'status'     => 'approved',
            'admin_note' => 'Tác phẩm rất chất lượng! Ban Biên Tập đã duyệt và sẽ liên hệ qua Zalo.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $req->refresh();
        $this->assertEquals('approved', $req->status);
        $this->assertEquals('Tác phẩm rất chất lượng! Ban Biên Tập đã duyệt và sẽ liên hệ qua Zalo.', $req->admin_note);
        $this->assertEquals($this->adminUser->id, $req->reviewed_by);

        // Kiểm tra user nhận được Announcement / Notification
        $this->assertDatabaseHas('announcements', [
            'target_user_id' => $this->regularUser->id,
            'audience'       => 'user',
            'severity'       => 'success',
        ]);
    }

    public function test_admin_can_delete_story_request(): void
    {
        $coverFile = UploadedFile::fake()->image('test_cover.jpg');
        $storedPath = $coverFile->store('story_requests/covers', 'public');

        $req = StoryPublishingRequest::create([
            'user_id'          => $this->regularUser->id,
            'creator_name'     => 'Author To Delete',
            'email'            => 'del@test.com',
            'phone_or_social'  => '0999999999',
            'experience_level' => 'beginner',
            'story_title'      => 'Truyện Sắp Bị Xóa',
            'story_type'       => 'novel',
            'genres'           => ['Hài Hước'],
            'story_status'     => 'ongoing',
            'summary'          => 'Tóm tắt ngắn gọn của truyện sắp bị xóa khỏi hệ thống...',
            'cover_image_path' => $storedPath,
            'status'           => 'rejected',
        ]);

        Storage::disk('public')->assertExists($storedPath);

        $response = $this->actingAs($this->adminUser)->delete(route('admin.storyRequests.destroy', $req->id));
        $response->assertRedirect(route('admin.storyRequests.index'));

        $this->assertDatabaseMissing('story_publishing_requests', ['id' => $req->id]);
        Storage::disk('public')->assertMissing($storedPath);
    }

    public function test_non_admin_cannot_access_admin_story_requests(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.storyRequests.index'));
        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }
}
