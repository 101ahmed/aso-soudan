@php
    $isAr = ($locale ?? 'ar') === 'ar';
    $dir = 'ltr';
    $align = $isAr ? 'right' : 'left';
    $t = [
        'ar' => [
            'org' => 'الرابطة السودانية برين',
            'secretariat' => 'الأمانة الأكاديمية',
            'space' => 'مساحة المعلم',
            'title' => 'تحضير حصة',
            'teacher' => 'المعلم',
            'subject' => 'المادة',
            'level' => 'الصف / المستوى',
            'date' => 'تاريخ الحصة',
            'lessonTitle' => 'عنوان الدرس',
            'unit' => 'الوحدة / المحور',
            'sectionBasics' => 'بيانات الحصة',
            'sectionPlan' => 'خطة سير الحصة',
            'objectives' => 'أهداف الدرس',
            'skills' => 'المهارات المستهدفة',
            'concepts' => 'المفاهيم والمعلومات الأساسية',
            'intro' => 'التمهيد',
            'explanation' => 'شرح الدرس',
            'activities' => 'الأنشطة والتطبيقات',
            'groupWork' => 'العمل الفردي / الجماعي',
            'assessment' => 'التقويم أثناء الحصة',
            'conclusion' => 'الخاتمة والواجب المنزلي',
            'empty' => '—',
            'footer' => 'وثيقة داخلية — الرابطة السودانية برين',
            'generated' => 'تاريخ الإصدار',
        ],
        'fr' => [
            'org' => 'Association soudanaise de Rennes',
            'secretariat' => 'Secrétariat académique',
            'space' => 'Espace enseignant',
            'title' => 'Préparation de cours',
            'teacher' => 'Enseignant',
            'subject' => 'Matière',
            'level' => 'Classe / niveau',
            'date' => 'Date de la séance',
            'lessonTitle' => 'Titre de la leçon',
            'unit' => 'Unité / axe',
            'sectionBasics' => 'Fiche de la séance',
            'sectionPlan' => 'Déroulement de la séance',
            'objectives' => 'Objectifs de la leçon',
            'skills' => 'Compétences visées',
            'concepts' => 'Notions et informations essentielles',
            'intro' => 'Mise en train',
            'explanation' => 'Explication de la leçon',
            'activities' => 'Activités et applications',
            'groupWork' => 'Travail individuel / en groupe',
            'assessment' => 'Évaluation pendant la séance',
            'conclusion' => 'Conclusion et devoirs',
            'empty' => '—',
            'footer' => 'Document interne — Association soudanaise de Rennes',
            'generated' => 'Émis le',
        ],
        'en' => [
            'org' => 'Sudanese Association of Rennes',
            'secretariat' => 'Academic Secretariat',
            'space' => 'Teacher space',
            'title' => 'Lesson preparation',
            'teacher' => 'Teacher',
            'subject' => 'Subject',
            'level' => 'Class / level',
            'date' => 'Lesson date',
            'lessonTitle' => 'Lesson title',
            'unit' => 'Unit / theme',
            'sectionBasics' => 'Lesson details',
            'sectionPlan' => 'Lesson flow',
            'objectives' => 'Lesson objectives',
            'skills' => 'Target skills',
            'concepts' => 'Key concepts and information',
            'intro' => 'Warm-up',
            'explanation' => 'Lesson explanation',
            'activities' => 'Activities and practice',
            'groupWork' => 'Individual / group work',
            'assessment' => 'In-class assessment',
            'conclusion' => 'Conclusion and homework',
            'empty' => '—',
            'footer' => 'Internal document — Sudanese Association of Rennes',
            'generated' => 'Issued',
        ],
    ][$locale] ?? [];
    $label = fn ($key) => $t[$key] ?? $key;
    $localized = function ($item) use ($isAr) {
        if (! $item) {
            return null;
        }
        $ar = $item->name_ar ?? null;
        $fr = $item->name_fr ?? null;

        return $isAr ? ($ar ?: $fr) : ($fr ?: $ar);
    };
    $text = function (?string $value) use ($label) {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : $label('empty');
    };
    $subjectName = $localized($prep->subject) ?: $label('empty');
    $levelName = $localized($prep->level) ?: $label('empty');
    $teacherName = $prep->teacher?->full_name ?: $label('empty');
    $date = $prep->lesson_date?->format('d/m/Y') ?: $label('empty');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ $label('title') }} — {{ $prep->title }}</title>
    <style>
        body { font-family: DejaVu Sans, Tahoma, Arial, sans-serif; color: #1e293b; font-size: 12px; margin: 24px; text-align: {{ $align }}; direction: ltr; unicode-bidi: bidi-override; }
        h1 { font-size: 20px; margin: 4px 0 2px; color: #134e4a; }
        h2 { font-size: 13px; margin: 0 0 10px; color: #0f766e; }
        .muted { color: #64748b; font-size: 10px; }
        .header { border-bottom: 2px solid #134e4a; padding-bottom: 10px; margin-bottom: 14px; }
        table.meta { width: 100%; border-collapse: collapse; }
        table.meta td { width: 50%; vertical-align: top; padding: 0 8px 8px 0; }
        .k { color: #0f766e; font-size: 10px; }
        .v { font-size: 13px; font-weight: bold; }
        .section { margin-top: 14px; font-size: 11px; font-weight: bold; color: #134e4a; text-transform: uppercase; letter-spacing: 0.04em; }
        .box { margin-top: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; }
        .box h3 { margin: 0 0 6px; font-size: 11px; color: #0f766e; }
        .notes { line-height: 1.55; white-space: pre-wrap; }
        .footer { margin-top: 22px; font-size: 9px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <p class="muted">{{ $label('org') }} — {{ $label('space') }}</p>
        <h1>{{ $label('title') }}</h1>
        <h2>{{ $prep->title }}</h2>
        <p class="muted">{{ $label('generated') }} {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <p class="section">{{ $label('sectionBasics') }}</p>
    <table class="meta">
        <tr>
            <td><div class="k">{{ $label('teacher') }}</div><div class="v">{{ $teacherName }}</div></td>
            <td><div class="k">{{ $label('date') }}</div><div class="v">{{ $date }}</div></td>
        </tr>
        <tr>
            <td><div class="k">{{ $label('subject') }}</div><div class="v">{{ $subjectName }}</div></td>
            <td><div class="k">{{ $label('level') }}</div><div class="v">{{ $levelName }}</div></td>
        </tr>
        <tr>
            <td><div class="k">{{ $label('lessonTitle') }}</div><div class="v">{{ $text($prep->title) }}</div></td>
            <td><div class="k">{{ $label('unit') }}</div><div class="v">{{ $text($prep->unit) }}</div></td>
        </tr>
    </table>

    <div class="box">
        <h3>{{ $label('objectives') }}</h3>
        <div class="notes">{{ $text($prep->objectives) }}</div>
    </div>
    <div class="box">
        <h3>{{ $label('skills') }}</h3>
        <div class="notes">{{ $text($prep->skills) }}</div>
    </div>
    <div class="box">
        <h3>{{ $label('concepts') }}</h3>
        <div class="notes">{{ $text($prep->concepts) }}</div>
    </div>

    <p class="section">{{ $label('sectionPlan') }}</p>
    <div class="box">
        <h3>{{ $label('intro') }}</h3>
        <div class="notes">{{ $text($prep->intro) }}</div>
    </div>
    <div class="box">
        <h3>{{ $label('explanation') }}</h3>
        <div class="notes">{{ $text($prep->explanation) }}</div>
    </div>
    <div class="box">
        <h3>{{ $label('activities') }}</h3>
        <div class="notes">{{ $text($prep->activities) }}</div>
    </div>
    <div class="box">
        <h3>{{ $label('groupWork') }}</h3>
        <div class="notes">{{ $text($prep->group_work) }}</div>
    </div>
    <div class="box">
        <h3>{{ $label('assessment') }}</h3>
        <div class="notes">{{ $text($prep->assessment) }}</div>
    </div>
    <div class="box">
        <h3>{{ $label('conclusion') }}</h3>
        <div class="notes">{{ $text($prep->conclusion) }}</div>
    </div>

    <p class="footer">{{ $label('footer') }}</p>
</body>
</html>
