<?php

namespace Tests\Feature;

use App\Models\DmcaReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DmcaReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_dmca_page(): void
    {
        $response = $this->get(route('dmca.show'));
        $response->assertOk();
        $response->assertSee('Chính Sách Bản Quyền');
        $response->assertSee('Biểu Mẫu Yêu Cầu Gỡ Bỏ Bản Quyền');
    }

    public function test_user_can_submit_dmca_report(): void
    {
        $payload = [
            'full_name'            => 'Nguyễn Văn Bản Quyền',
            'email'                => 'copyright@author.com',
            'company_name'         => 'NXB Kim Đồng',
            'work_title'           => 'Thám Tử Lừng Danh Conan',
            'infringing_url'       => 'https://webcomics.vn/truyen/conan',
            'original_work_proof'  => 'https://publisher.com/proof/conan',
            'details'              => 'Yêu cầu gỡ bỏ toàn bộ các chương.',
            'good_faith_statement' => '1',
        ];

        $response = $this->post(route('dmca.store'), $payload);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dmca_reports', [
            'full_name'  => 'Nguyễn Văn Bản Quyền',
            'email'      => 'copyright@author.com',
            'work_title' => 'Thám Tử Lừng Danh Conan',
            'status'     => 'pending',
        ]);
    }
}
