<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileFooterAccordionTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_renders_accordion_items_with_accessibility_attributes(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();

        // Kiểm tra cấu trúc footer chính
        $response->assertSee('site-footer');
        $response->assertSee('footer-main-grid');

        // Kiểm tra cấu trúc accordion 3 mục chính
        $response->assertSee('footer-accordion-item');
        $response->assertSee('fcol-accordion-btn');
        $response->assertSee('aria-expanded="false"', false);
        $response->assertSee('aria-controls="fcol-collapse-explore"', false);
        $response->assertSee('aria-controls="fcol-collapse-account"', false);
        $response->assertSee('aria-controls="fcol-collapse-support"', false);

        // Kiểm tra tiêu đề các mục accordion
        $response->assertSee('Khám Phá');
        $response->assertSee('Tài Khoản');
        $response->assertSee('Hỗ Trợ');

        // Kiểm tra icon mở đóng (+ / -)
        $response->assertSee('fcol-accordion-icon');
        $response->assertSee('icon-plus');
        $response->assertSee('icon-minus');

        // Kiểm tra thanh footer dưới đáy trên mobile
        $response->assertSee('footer-bottom-mobile');
        $response->assertSee('fcopy-text-mobile');
        $response->assertSee('footer-legal-links-mobile');
        $response->assertSee('Điều Khoản');
        $response->assertSee('Chính Sách Bảo Mật');

        // Kiểm tra thanh footer desktop vẫn được giữ nguyên
        $response->assertSee('footer-bottom-bar');
        $response->assertSee('All rights reserved.');
    }

    public function test_all_expected_footer_links_are_present_in_accordion(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();

        // 1. Nhóm Khám Phá
        $response->assertSee(route('home'));
        $response->assertSee(route('genres'));
        $response->assertSee(route('schedule'));
        $response->assertSee(route('schedule.completed'));
        $response->assertSee(route('originals'));

        // 2. Nhóm Tài Khoản (khách vãng lai)
        $response->assertSee(route('login'));
        $response->assertSee(route('register'));

        // 3. Nhóm Hỗ Trợ
        $response->assertSee(route('pages.about'));
        $response->assertSee(route('pages.terms'));
        $response->assertSee(route('pages.privacy'));
        $response->assertSee(route('pages.contact'));
        $response->assertSee(route('dmca.show'));
        $response->assertSee(route('teams.index'));
        $response->assertSee(route('sitemap'));
    }
}
