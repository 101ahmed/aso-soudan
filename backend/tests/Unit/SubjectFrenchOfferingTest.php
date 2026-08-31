<?php

namespace Tests\Unit;

use App\Models\Subject;
use App\Support\FrenchAdultCatalog;
use PHPUnit\Framework\TestCase;

class SubjectFrenchOfferingTest extends TestCase
{
    public function test_childrens_french_stays_excluded(): void
    {
        $byCode = new Subject(['code' => 'FR', 'name_ar' => 'Something else']);
        $byName = new Subject(['code' => 'XX', 'name_ar' => 'اللغة الفرنسية']);

        $this->assertTrue(Subject::isFrenchLanguage($byCode));
        $this->assertTrue(Subject::isFrenchLanguage($byName));
    }

    public function test_adult_french_is_offered(): void
    {
        $adult = new Subject([
            'code' => FrenchAdultCatalog::SUBJECT_CODE,
            'name_ar' => FrenchAdultCatalog::SUBJECT_NAME_AR,
            'name_fr' => FrenchAdultCatalog::SUBJECT_NAME_FR,
        ]);

        $this->assertFalse(Subject::isFrenchLanguage($adult));
    }
}
