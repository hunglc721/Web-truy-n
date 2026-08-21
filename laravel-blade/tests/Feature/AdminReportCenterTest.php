<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportCenterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $member;
    protected Comic $comic;
    protected Chapter $chapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->member = User::factory()->create(['is_admin' => false]);

        $this->comic = Comic::factory()->create();
        $this->chapter = Chapter::factory()->create([
            'comic_id'     => $this->comic->id,
            'published_at' => now()->subDay(),
        ]);
    }

    public function test_report_from_reader_creates_record_instantly_and_appears_in_admin(): void
    {
        // 1. Gửi báo cáo từ Reader (FE-03)
        $response = $this->actingAs($this->member)->postJson(route('reports.store'), [
            'comic_id'    => $this->comic->id,
            'chapter_id'  => $this->chapter->id,
            'page_number' => 4,
            'image_url'   => 'https://cdn.example.com/broken-page-4.jpg',
            'type'        => 'broken_image',
            'description' => 'Ảnh số 4 bị đen xì không load được',
        ]);
        $response->assertCreated()->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('reports', [
            'comic_id'    => $this->comic->id,
            'chapter_id'  => $this->chapter->id,
            'page_number' => 4,
            'status'      => 'pending',
        ]);

        // 2. Admin xem danh sách báo cáo
        $adminRes = $this->actingAs($this->admin)->get(route('admin.reports.index'));
        $adminRes->assertOk();
        $adminRes->assertSee('Ảnh số 4 bị đen xì không load được');
        $adminRes->assertSee('Trang 4');
        $adminRes->assertSee('#page-4');
    }

    public function test_admin_can_update_report_status_and_add_admin_note(): void
    {
        $report = Report::create([
            'comic_id'    => $this->comic->id,
            'chapter_id'  => $this->chapter->id,
            'page_number' => 2,
            'type'        => 'broken_image',
            'status'      => 'pending',
            'description' => 'Trang 2 bị lỗi',
        ]);

        // 1. Chuyển sang Processing
        $resProc = $this->actingAs($this->admin)->patch(route('admin.reports.updateStatus', $report), [
            'status'     => 'processing',
            'admin_note' => 'Đang tải lại ảnh từ server nguồn',
        ]);
        $resProc->assertRedirect();

        $report->refresh();
        $this->assertEquals(Report::STATUS_PROCESSING, $report->status);
        $this->assertEquals('Đang tải lại ảnh từ server nguồn', $report->admin_note);

        // 2. Chuyển sang Resolved
        $resRes = $this->actingAs($this->admin)->patch(route('admin.reports.updateStatus', $report), [
            'status'     => 'resolved',
            'admin_note' => 'Đã re-upload ảnh mới chất lượng HD',
        ]);
        $resRes->assertRedirect();

        $report->refresh();
        $this->assertEquals(Report::STATUS_RESOLVED, $report->status);
        $this->assertEquals('Đã re-upload ảnh mới chất lượng HD', $report->admin_note);
    }

    public function test_admin_can_filter_and_delete_report(): void
    {
        $report = Report::create([
            'comic_id'    => $this->comic->id,
            'chapter_id'  => $this->chapter->id,
            'page_number' => 1,
            'type'        => 'wrong_order',
            'status'      => 'resolved',
        ]);

        // Lọc theo status
        $filterRes = $this->actingAs($this->admin)->get(route('admin.reports.index', ['status' => 'resolved']));
        $filterRes->assertOk();
        $filterRes->assertViewHas('reports');

        // Xóa báo cáo
        $delRes = $this->actingAs($this->admin)->delete(route('admin.reports.destroy', $report));
        $delRes->assertRedirect();

        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }
}
