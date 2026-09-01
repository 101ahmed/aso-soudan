<?php

namespace Tests\Unit;

use App\Support\ExtensionOnlyMimeTypeDetector;
use PHPUnit\Framework\TestCase;

class ExtensionOnlyMimeTypeDetectorTest extends TestCase
{
    public function test_it_maps_image_extensions_without_finfo(): void
    {
        $detector = new ExtensionOnlyMimeTypeDetector;

        $this->assertSame('image/jpeg', $detector->detectMimeTypeFromPath('photo.jpg'));
        $this->assertSame('image/png', $detector->detectMimeTypeFromPath('officers/academic/a.png'));
        $this->assertSame('image/jpeg', $detector->detectMimeType('x.jpeg', null));
        $this->assertNull($detector->detectMimeTypeFromBuffer('raw'));
    }
}
