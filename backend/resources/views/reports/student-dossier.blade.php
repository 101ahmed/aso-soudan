@php
    $isAr = ($locale ?? 'ar') === 'ar';
    $dir = 'ltr';
    $align = $isAr ? 'right' : 'left';
    $t = [
        'ar' => [
            'org' => 'الرابطة السودانية برين',
            'secretariat' => 'الأمانة الأكاديمية',
            'title' => 'ملف الطالب',
            'name' => 'الاسم',
            'age' => 'العمر',
            'years' => 'سنة',
            'level' => 'المستوى',
            'stage' => 'المرحلة',
            'year' => 'السنة الدراسية',
            'gender' => 'الجنس',
            'male' => 'ذكر',
            'female' => 'أنثى',
            'birthDate' => 'تاريخ الميلاد',
            'subjects' => 'المواد',
            'notes' => 'ملاحظات مرشد الصف',
            'noPhoto' => 'لا توجد صورة',
            'empty' => '—',
            'footer' => 'وثيقة داخلية — الرابطة السودانية برين',
            'generated' => 'تاريخ الإصدار',
        ],
        'fr' => [
            'org' => 'Association soudanaise de Rennes',
            'secretariat' => 'Secrétariat académique',
            'title' => 'Dossier de l’élève',
            'name' => 'Nom',
            'age' => 'Âge',
            'years' => 'ans',
            'level' => 'Niveau',
            'stage' => 'Cycle',
            'year' => 'Année scolaire',
            'gender' => 'Genre',
            'male' => 'Garçon',
            'female' => 'Fille',
            'birthDate' => 'Date de naissance',
            'subjects' => 'Matières',
            'notes' => 'Observations du conseiller de classe',
            'noPhoto' => 'Pas de photo',
            'empty' => '—',
            'footer' => 'Document interne — Association soudanaise de Rennes',
            'generated' => 'Émis le',
        ],
        'en' => [
            'org' => 'Sudanese Association of Rennes',
            'secretariat' => 'Academic Secretariat',
            'title' => 'Student file',
            'name' => 'Name',
            'age' => 'Age',
            'years' => 'years',
            'level' => 'Level',
            'stage' => 'Stage',
            'year' => 'Academic year',
            'gender' => 'Gender',
            'male' => 'Boy',
            'female' => 'Girl',
            'birthDate' => 'Date of birth',
            'subjects' => 'Subjects',
            'notes' => 'Class counselor notes',
            'noPhoto' => 'No photo',
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
    $levelName = $localized($student->level) ?: $label('empty');
    $stageName = $localized($student->educationStage) ?: $label('empty');
    $yearName = $student->academicYear?->name ?: $label('empty');
    $gender = $student->gender ? $label($student->gender) : $label('empty');
    $birth = $student->birth_date?->format('d/m/Y') ?: $label('empty');
    $age = $student->age !== null ? $student->age.' '.$label('years') : $label('empty');
    $subjects = $student->subjects
        ->reject(fn ($subject) => \App\Models\Subject::isFrenchLanguage($subject))
        ->map(fn ($subject) => $localized($subject))
        ->filter()
        ->implode(' · ');
    $notes = trim((string) $student->notes);
    $photoFirst = ! $isAr;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ $label('title') }} — {{ $student->full_name }}</title>
    <style>
        body { font-family: DejaVu Sans, Tahoma, Arial, sans-serif; color: #1e293b; font-size: 12px; margin: 28px; text-align: {{ $align }}; direction: ltr; unicode-bidi: bidi-override; }
        h1 { font-size: 20px; margin: 4px 0 2px; color: #134e4a; }
        h2 { font-size: 13px; margin: 0 0 10px; color: #0f766e; }
        .muted { color: #64748b; font-size: 10px; }
        .header { border-bottom: 2px solid #134e4a; padding-bottom: 10px; margin-bottom: 16px; }
        table.meta { width: 100%; border-collapse: collapse; }
        td.photo { width: 150px; vertical-align: top; text-align: center; }
        td.info { vertical-align: top; }
        .portrait { width: 130px; height: 160px; object-fit: cover; border: 1px solid #cbd5e1; }
        .placeholder { width: 130px; height: 160px; border: 1px dashed #94a3b8; color: #64748b; font-size: 10px; }
        .row { margin: 0 0 8px; }
        .k { color: #0f766e; font-size: 10px; }
        .v { font-size: 13px; font-weight: bold; }
        .box { margin-top: 18px; border: 1px solid #cbd5e1; padding: 12px 14px; }
        .box h3 { margin: 0 0 8px; font-size: 12px; color: #134e4a; }
        .notes { min-height: 90px; line-height: 1.55; white-space: pre-wrap; }
        .footer { margin-top: 28px; font-size: 9px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <p class="muted">{{ $label('org') }}</p>
        <h1>{{ $label('title') }}</h1>
        <h2>{{ $label('secretariat') }}</h2>
        <p class="muted">{{ $label('generated') }} {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table class="meta">
        <tr>
            @if($photoFirst)
                <td class="photo">
                    @if($photoSrc)
                        <img class="portrait" src="{{ $photoSrc }}" alt="">
                    @else
                        <div class="placeholder">{{ $label('noPhoto') }}</div>
                    @endif
                </td>
            @endif
            <td class="info">
                <div class="row"><div class="k">{{ $label('name') }}</div><div class="v">{{ $student->full_name }}</div></div>
                <div class="row"><div class="k">{{ $label('age') }}</div><div class="v">{{ $age }}</div></div>
                <div class="row"><div class="k">{{ $label('birthDate') }}</div><div class="v">{{ $birth }}</div></div>
                <div class="row"><div class="k">{{ $label('level') }}</div><div class="v">{{ $levelName }}</div></div>
                <div class="row"><div class="k">{{ $label('stage') }}</div><div class="v">{{ $stageName }}</div></div>
                <div class="row"><div class="k">{{ $label('year') }}</div><div class="v">{{ $yearName }}</div></div>
                <div class="row"><div class="k">{{ $label('gender') }}</div><div class="v">{{ $gender }}</div></div>
                <div class="row"><div class="k">{{ $label('subjects') }}</div><div class="v">{{ $subjects ?: $label('empty') }}</div></div>
            </td>
            @unless($photoFirst)
                <td class="photo">
                    @if($photoSrc)
                        <img class="portrait" src="{{ $photoSrc }}" alt="">
                    @else
                        <div class="placeholder">{{ $label('noPhoto') }}</div>
                    @endif
                </td>
            @endunless
        </tr>
    </table>

    <div class="box">
        <h3>{{ $label('notes') }}</h3>
        <div class="notes">{{ $notes !== '' ? $notes : $label('empty') }}</div>
    </div>

    <p class="footer">{{ $label('footer') }}</p>
</body>
</html>
