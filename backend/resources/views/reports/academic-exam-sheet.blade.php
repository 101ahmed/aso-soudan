@php
    $isAr = ($locale ?? 'ar') === 'ar';
    $dir = $isAr ? 'rtl' : 'ltr';
    $exam = $payload['exam'] ?? [];
    $roster = $payload['roster'] ?? [];
    $stats = $payload['stats'] ?? [];
    $t = [
        'ar' => [
            'org' => 'الرابطة السودانية برين',
            'title' => 'كشف درجات الامتحان',
            'level' => 'المستوى',
            'subject' => 'المادة',
            'date' => 'التاريخ',
            'period' => 'الفترة',
            'max' => 'الدرجة الكاملة',
            'pass' => 'درجة النجاح',
            'student' => 'الطالب',
            'score' => 'الدرجة',
            'percent' => 'النسبة',
            'result' => 'النتيجة',
            'absent' => 'غائب',
            'passed' => 'ناجح',
            'failed' => 'راسب',
            'entered' => 'الدرجات المدخلة',
            'average' => 'معدل الصف',
            'passCount' => 'ناجحون',
            'empty' => '—',
            'footer' => 'وثيقة داخلية — الرابطة السودانية برين',
            'term1' => 'الفصل الأول',
            'term2' => 'الفصل الثاني',
            'term3' => 'الفصل الثالث',
            'annual' => 'سنوي',
        ],
        'fr' => [
            'org' => 'Association soudanaise de Rennes',
            'title' => 'Feuille de notes',
            'level' => 'Niveau',
            'subject' => 'Matière',
            'date' => 'Date',
            'period' => 'Période',
            'max' => 'Note max.',
            'pass' => 'Seuil',
            'student' => 'Élève',
            'score' => 'Note',
            'percent' => '%',
            'result' => 'Résultat',
            'absent' => 'Absent',
            'passed' => 'Admis',
            'failed' => 'Ajourné',
            'entered' => 'Notes saisies',
            'average' => 'Moyenne de la classe',
            'passCount' => 'Admis',
            'empty' => '—',
            'footer' => 'Document interne — Association soudanaise de Rennes',
            'term1' => '1er trimestre',
            'term2' => '2e trimestre',
            'term3' => '3e trimestre',
            'annual' => 'Annuel',
        ],
        'en' => [
            'org' => 'Sudanese Association of Rennes',
            'title' => 'Grade sheet',
            'level' => 'Level',
            'subject' => 'Subject',
            'date' => 'Date',
            'period' => 'Period',
            'max' => 'Max',
            'pass' => 'Pass',
            'student' => 'Student',
            'score' => 'Score',
            'percent' => '%',
            'result' => 'Result',
            'absent' => 'Absent',
            'passed' => 'Passed',
            'failed' => 'Failed',
            'entered' => 'Grades entered',
            'average' => 'Class average',
            'passCount' => 'Passed',
            'empty' => '—',
            'footer' => 'Internal document — Sudanese Association of Rennes',
            'term1' => 'Term 1',
            'term2' => 'Term 2',
            'term3' => 'Term 3',
            'annual' => 'Annual',
        ],
    ][$locale ?? 'ar'] ?? [];
    $name = function ($item) use ($isAr, $t) {
        if (! is_array($item) || ! $item) {
            return $t['empty'];
        }

        return $isAr
            ? ($item['name_ar'] ?? $item['name'] ?? $t['empty'])
            : ($item['name_fr'] ?? $item['name_ar'] ?? $item['name'] ?? $t['empty']);
    };
    $result = function ($row) use ($t) {
        if (! empty($row['is_absent'])) {
            return $t['absent'];
        }
        if (($row['passed'] ?? null) === true) {
            return $t['passed'];
        }
        if (($row['passed'] ?? null) === false) {
            return $t['failed'];
        }

        return $t['empty'];
    };
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: {{ $isAr ? 'right' : 'left' }}; }
        th { background: #f1f5f9; }
        .muted { color: #64748b; font-size: 11px; }
        .kpis td { border: none; padding: 4px 0; }
    </style>
</head>
<body>
    <p class="muted">{{ $t['org'] }}</p>
    <h1>{{ $exam['title'] ?? $t['title'] }}</h1>
    <table class="kpis">
        <tr><td>{{ $t['level'] }}</td><td>{{ $name($exam['level'] ?? null) }}</td></tr>
        <tr><td>{{ $t['subject'] }}</td><td>{{ $name($exam['subject'] ?? null) }}</td></tr>
        <tr><td>{{ $t['date'] }}</td><td>{{ $exam['exam_date'] ?? $t['empty'] }}</td></tr>
        <tr><td>{{ $t['period'] }}</td><td>{{ $t[$exam['period'] ?? ''] ?? ($exam['period'] ?? $t['empty']) }}</td></tr>
        <tr><td>{{ $t['max'] }}</td><td>{{ $exam['max_score'] ?? $t['empty'] }}</td></tr>
        <tr><td>{{ $t['pass'] }}</td><td>{{ $exam['pass_score'] ?? $t['empty'] }}</td></tr>
        <tr><td>{{ $t['entered'] }}</td><td>{{ ($stats['entered'] ?? 0) }}/{{ ($stats['students'] ?? 0) }}</td></tr>
        <tr><td>{{ $t['average'] }}</td><td>{{ $stats['average'] ?? $t['empty'] }}</td></tr>
        <tr><td>{{ $t['passCount'] }}</td><td>{{ $stats['pass_count'] ?? 0 }}</td></tr>
    </table>
    <table>
        <thead>
            <tr>
                <th>{{ $t['student'] }}</th>
                <th>{{ $t['score'] }}</th>
                <th>{{ $t['percent'] }}</th>
                <th>{{ $t['result'] }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($roster as $row)
                <tr>
                    <td>{{ $row['full_name'] ?? $t['empty'] }}</td>
                    <td>{{ !empty($row['is_absent']) ? $t['absent'] : ($row['score'] ?? $t['empty']) }}</td>
                    <td>{{ $row['percent'] ?? $t['empty'] }}</td>
                    <td>{{ $result($row) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">{{ $t['empty'] }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="muted" style="margin-top:24px">{{ $t['footer'] }}</p>
</body>
</html>
