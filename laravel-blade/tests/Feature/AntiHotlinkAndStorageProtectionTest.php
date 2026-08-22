<?php

namespace Tests\Feature;

use App\Http\Middleware\ProtectChapterImages;
use Illuminate\Http\Request;
use Tests\TestCase;

class AntiHotlinkAndStorageProtectionTest extends TestCase
{
    public function test_external_referer_is_blocked_by_anti_hotlink_middleware(): void
    {
        $middleware = new ProtectChapterImages();

        $request = Request::create('/storage/chapters/1/1/001.jpg', 'GET');
        $request->headers->set('referer', 'https://malicious-scraper-site.com/view');

        $response = $middleware->handle($request, function () {
            return response('Image served', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Hotlinking is not allowed', $response->getContent());
    }

    public function test_internal_referer_is_allowed(): void
    {
        $middleware = new ProtectChapterImages();

        $request = Request::create('/storage/chapters/1/1/001.jpg', 'GET');
        $request->headers->set('referer', config('app.url') . '/truyen/solo-leveling/chapter-1');

        $response = $middleware->handle($request, function () {
            return response('Image served', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}
