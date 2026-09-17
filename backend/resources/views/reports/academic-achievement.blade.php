@php
    $isAr = ($locale ?? 'ar') === 'ar';
    $dir = $isAr ? 'rtl' : 'ltr';
    $subjects = $report['subjects'] ?? [];
    $students = $report['students'] ?? [];
    $t = [
        'ar' => [
            'org' => 'الرابطة السودانية برين',
            'title' => 'التحصيل الدراسي',
            'level' => 'المستوى',
            'year' => 'السنة الدراسية',
            'period' => 'الفترة',
            'classAverage' => 'معدل الصف',
            'student' => 'الطالب',
            'average' => 'معدل الفترة',
            'yearGrade' => 'درجة السنة',
            'previous' => 'الفترة السابقة',
            'progress' => 'التطور',
            'up' => 'ارتفاع',
            'down' => 'انخفاض',
            'same' => 'مستقر',
            'empty' => 'لا توجد درجات',
            'dash' => '—',
            'footer' => 'وثيقة داخلية — الرابطة السودانية برين',
            'term1' => 'الفصل الأول',
            'term2' => 'الفصل الثاني',
            'term3' => 'الفصل الثالث',
            'annual' => 'سنوي',
        ],
        'fr' => [
            'org' => 'Association soudanaise de Rennes',
            'title' => 'Résultats scolaires',
            'level' => 'Niveau',
            'year' => 'Année scolaire',
            'period' => 'Période',
            'classAverage' => 'Moyenne de la classe',
            'student' => 'Élève',
            'average' => 'Moyenne de période',
            'yearGrade' => 'Note annuelle',
            'previous' => 'Période précédente',
            'progress' => 'Évolution',
            'up' => 'En hausse',
            'down' => 'En baisse',
            'same' => 'Stable',
            'empty' => 'Aucune note',
            'dash' => '—',
            'footer' => 'Document interne — Association soudanaise de Rennes',
            'term1' => '1er trimestre',
            'term2' => '2e trimestre',
            'term3' => '3e trimestre',
            'annual' => 'Annuel',
        ],
        'en' => [
            'org' => 'Sudanese Association of Rennes',
            'title' => 'Academic results',
            'level' => 'Level',
            'year' => 'Academic year',
            'period' => 'Period',
            'classAverage' => 'Class average',
            'student' => 'Student',
            'average' => 'Period average',
            'yearGrade' => 'Year grade',
            'previous' => 'Previous period',
            'progress' => 'Progress',
            'up' => 'Up',
            'down' => 'Down',
            'same' => 'Stable',
            'empty' => 'No grades',
            'dash' => '—',
            'footer' => 'Internal document — Sudanese Association of Rennes',
            'term1' => 'Term 1',
            'term2' => 'Term 2',
            'term3' => 'Term 3',
            'annual' => 'Annual',
        ],
    ][$locale ?? 'ar'] ?? [];
    $name = function ($item) use ($isAr, $t) {
        if (! is_array($item) || ! $item) {
            return $t['dash'];
        }

        return $isAr
            ? ($item['name_ar'] ?? $item['name'] ?? $t['dash'])
            : ($item['name_fr'] ?? $item['name_ar'] ?? $item['name'] ?? $t['dash']);
    };
    $trend = function ($value) use ($t) {
        return $t[$value] ?? $t['dash'];
    };
    $cell = function ($row, $subjectId) use ($t) {
        $map = $row['subjects'] ?? [];

        return $map[$subjectId] ?? $map[(string) $subjectId] ?? $t['dash'];
    };
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: {{ $isAr ? 'right' : 'left' }}; }
        th { background: #f1f5f9; }
        .muted { color: #64748b; font-size: 10px; }
        .kpis td { border: none; padding: 3px 0; }
    </style>
</head>
<body>
    <p class="muted">{{ $t['org'] }}</p>
    <h1>{{ $t['title'] }}</h1>
    <table class="kpis">
        <tr><td>{{ $t['year'] }}</td><td>{{ $report['academic_year']['name'] ?? $t['dash'] }}</td></tr>
        <tr><td>{{ $t['level'] }}</td><td>{{ $name($report['level'] ?? null) }}</td></tr>
        <tr><td>{{ $t['period'] }}</td><td>{{ $t[$report['period'] ?? ''] ?? ($report['period'] ?? $t['dash']) }}</td></tr>
        <tr><td>{{ $t['classAverage'] }}</td><td>{{ $report['class_average'] ?? $t['dash'] }}</td></tr>
    </table>
    <table>
        <thead>
            <tr>
                <th>{{ $t['student'] }}</th>
                @foreach($subjects as $subject)
                    <th>{{ $name($subject) }}</th>
                @endforeach
                <th>{{ $t['average'] }}</th>
                <th>{{ $t['yearGrade'] }}</th>
                <th>{{ $t['previous'] }}</th>
                <th>{{ $t['progress'] }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $row)
                <tr>
                    <td>{{ $row['full_name'] ?? $t['dash'] }}</td>
                    @foreach($subjects as $subject)
                        <td>{{ $cell($row, $subject['id'] ?? null) }}</td>
                    @endforeach
                    <td>{{ $row['average'] ?? $t['dash'] }}</td>
                    <td>{{ $row['year_average'] ?? $t['dash'] }}</td>
                    <td>{{ $row['previous_average'] ?? $t['dash'] }}</td>
                    <td>{{ $trend($row['trend'] ?? null) }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ count($subjects) + 5 }}">{{ $t['empty'] }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="muted" style="margin-top:24px">{{ $t['footer'] }}</p>
</body>
</html>
