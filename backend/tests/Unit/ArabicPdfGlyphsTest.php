<?php

namespace Tests\Unit;

use App\Support\ArabicPdfGlyphs;
use PHPUnit\Framework\TestCase;

class ArabicPdfGlyphsTest extends TestCase
{
    public function test_isolated_beh_uses_presentation_form(): void
    {
        $this->assertSame("\u{FE8F}", ArabicPdfGlyphs::shape('ب'));
    }

    public function test_arabic_is_not_the_naive_character_reverse(): void
    {
        $logical = 'المستوى';
        $naive = implode('', array_reverse(preg_split('//u', $logical, -1, PREG_SPLIT_NO_EMPTY) ?: []));
        $shaped = ArabicPdfGlyphs::shape($logical);

        $this->assertNotSame($naive, $shaped);
        $this->assertNotSame($logical, $shaped);
        $this->assertMatchesRegularExpression('/[\x{FE70}-\x{FEFF}]/u', $shaped);
    }

    public function test_association_name_is_shaped_and_keeps_rennes(): void
    {
        $logical = 'رابطة الجالية السودانية برين';
        $shaped = ArabicPdfGlyphs::shape($logical);
        $berlin = ArabicPdfGlyphs::shape('رابطة الجالية السودانية برلين');

        $this->assertNotSame($logical, $shaped);
        $this->assertNotSame($berlin, $shaped);
        $this->assertStringNotContainsString('برلين', $shaped);
    }

    public function test_latin_and_digits_stay_ltr_inside_mixed_line(): void
    {
        $shaped = ArabicPdfGlyphs::shape('السبت 29/08');

        $this->assertStringContainsString('29/08', $shaped);
        $this->assertMatchesRegularExpression('/[\x{FE70}-\x{FEFF}]/u', $shaped);
    }

    public function test_html_text_nodes_are_shaped_tags_are_not(): void
    {
        $html = '<h1>جدول الحضور والغياب</h1>';
        $shaped = ArabicPdfGlyphs::shapeHtml($html);

        $this->assertStringStartsWith('<h1>', $shaped);
        $this->assertStringEndsWith('</h1>', $shaped);
        $this->assertStringNotContainsString('بايغلاو', $shaped);
        $this->assertMatchesRegularExpression('/[\x{FE70}-\x{FEFF}]/u', $shaped);
    }
}
