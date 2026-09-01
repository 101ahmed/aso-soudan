<?php

namespace Tests\Feature;

use App\Support\MediaUrl;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficerCardStorageTest extends TestCase
{
    public function test_media_url_builds_public_path_without_flysystem(): void
    {
        $this->assertNull(MediaUrl::absolute(null));
        $this->assertNull(MediaUrl::absolute(''));

        $url = MediaUrl::absolute('officers/academic/photo.jpg');
        $this->assertIsString($url);
        $this->assertStringContainsString('officers/academic/photo.jpg', $url);
        $this->assertStringStartsWith('http', $url);

        $this->assertSame(
            'https://cdn.example/photo.jpg',
            MediaUrl::absolute('https://cdn.example/photo.jpg')
        );
    }

    public function test_public_disk_can_write_without_finfo(): void
    {
        Storage::disk('public')->put('officers/_test.txt', 'ok');
        $this->assertTrue(Storage::disk('public')->exists('officers/_test.txt'));
        $this->assertSame('ok', Storage::disk('public')->get('officers/_test.txt'));
        Storage::disk('public')->delete('officers/_test.txt');
    }
}
