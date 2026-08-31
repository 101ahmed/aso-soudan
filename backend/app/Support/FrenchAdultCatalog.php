<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\EducationStage;
use App\Models\Level;
use App\Models\Subject;

class FrenchAdultCatalog
{
    public const SUBJECT_CODE = 'FR_ADULT';

    public const SUBJECT_NAME_AR = 'اللغة الفرنسية للبالغين';

    public const SUBJECT_NAME_FR = 'Français pour adultes';

    public const LEVEL_CODE = 'ADULT';

    public const LEVEL_NAME_AR = 'البالغين';

    public const LEVEL_NAME_FR = 'Adultes';

    /**
     * Idempotent catalog: adult French subject, adult level, and current-year class.
     * Does not revive the children's FR subject (code FR / اللغة الفرنسية).
     */
    public static function ensure(): void
    {
        $subject = self::ensureSubject();
        $level = self::ensureLevel();
        if (! $subject || ! $level) {
            return;
        }

        $level->subjects()->syncWithoutDetaching([$subject->id]);
        self::ensureClassGroup($subject, $level);
    }

    public static function ensureSubject(): Subject
    {
        $subject = Subject::query()->withTrashed()->firstOrNew(['code' => self::SUBJECT_CODE]);
        $subject->fill([
            'name_ar' => self::SUBJECT_NAME_AR,
            'name_fr' => self::SUBJECT_NAME_FR,
            'is_active' => true,
        ]);
        $subject->deleted_at = null;
        $subject->save();

        return $subject;
    }

    public static function ensureLevel(?EducationStage $stage = null): ?Level
    {
        $stage ??= EducationStage::query()
            ->where('is_active', true)
            ->where('code', 'primary')
            ->first()
            ?? EducationStage::query()->where('is_active', true)->orderBy('sort_order')->first();

        if (! $stage) {
            return null;
        }

        $level = Level::query()
            ->withTrashed()
            ->firstOrNew([
                'education_stage_id' => $stage->id,
                'code' => self::LEVEL_CODE,
            ]);
        $level->fill([
            'name_ar' => self::LEVEL_NAME_AR,
            'name_fr' => self::LEVEL_NAME_FR,
            'sort_order' => 50,
            'is_active' => true,
        ]);
        $level->deleted_at = null;
        $level->save();

        return $level;
    }

    public static function ensureClassGroup(Subject $subject, Level $level): ?ClassGroup
    {
        $year = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();

        if (! $year) {
            return null;
        }

        return ClassGroup::query()->updateOrCreate(
            [
                'academic_year_id' => $year->id,
                'subject_id' => $subject->id,
                'level_id' => $level->id,
            ],
            [
                'name' => $subject->name_ar.' — '.$level->name_ar,
                'code' => self::SUBJECT_CODE.'-'.self::LEVEL_CODE,
                'capacity' => 20,
                'status' => 'active',
            ]
        );
    }
}
