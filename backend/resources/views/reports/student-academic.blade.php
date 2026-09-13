@php
    $isAr = ($locale ?? 'ar') === 'ar';
    $dir = $isAr ? 'rtl' : 'ltr';
    $student = $report['student'] ?? [];
    $t = [
        'ar' => [
            'org' => 'الرابطة السودانية برين',
            'title' => 'تقرير أكاديمي مختصر',
            'name' => 'الطالب',
            'level' => 'المستوى',
            'year' => 'السنة الدراسية',
            'period' => 'الفترة',
            'average' => 'متوسط الطالب',
            'classAverage' => 'متوسط الفصل',
            'previous' => 'الفترة السابقة',
            'subjects' => 'درجات المواد',
            'exam' => 'الامتحان',
            'score' => 'الدرجة',
            'max' => 'النهاية',
            'pass' => 'النجاح',
            'percent' => 'النسبة',
            'progress' => 'تطور الطالب',
            'periods' => 'مقارنة الفترات',
            'empty' => '—',
            'footer' => 'وثيقة داخلية — الرابطة السودانية برين',
            'term1' => 'الفترة الأولى',
            'term2' => 'الفترة الثانية',
            'term3' => 'الفترة الثالثة',
            'annual' => 'سنوي',
        ],
        'fr' => [
            'org' => 'Association soudanaise de Rennes',
            'title' => 'Rapport académique',
            'name' => 'Élève',
            'level' => 'Niveau',
            'year' => 'Année scolaire',
            'period' => 'Période',
            'average' => 'Moyenne de l’élève',
            'classAverage' => 'Moyenne de la classe',
            'previous' => 'Période précédente',
            'subjects' => 'Notes par matière',
            'exam' => 'Examen',
            'score' => 'Note',
            'max' => 'Barème',
            'pass' => 'Seuil',
            'percent' => '%',
            'progress' => 'Évolution',
            'periods' => 'Comparaison des périodes',
            'empty' => '—',
            'footer' => 'Document interne — Association soudanaise de Rennes',
            'term1' => '1re période',
            'term2' => '2e période',
            'term3' => '3e période',
            'annual' => 'Annuel',
        ],
        'en' => [
            'org' => 'Sudanese Association of Rennes',
            'title' => 'Short academic report',
            'name' => 'Student',
            'level' => 'Level',
            'year' => 'Academic year',
            'period' => 'Period',
            'average' => 'Student average',
            'classAverage' => 'Class average',
            'previous' => 'Previous period',
            'subjects' => 'Subject grades',
            'exam' => 'Exam',
            'score' => 'Score',
            'max' => 'Max',
            'pass' => 'Pass',
            'percent' => '%',
            'progress' => 'Progress',
            'periods' => 'Period comparison',
            'empty' => '—',
            'footer' => 'Internal document — Sudanese Association of Rennes',
            'term1' => 'Term 1',
            'term2' => 'Term 2',
            'term3' => 'Term 3',
            'annual' => 'Annual',
        ],
    ][$locale ?? 'ar'] ?? [];
    $periodLabel = $t[$report['period'] ?? 'term1'] ?? ($report['period'] ?? '');
    $levelName = $isAr
        ? ($student['level']['name_ar'] ?? $t['empty'])
        : ($student['level']['name_fr'] ?? $student['level']['name_ar'] ?? $t['empty']);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: {{ $isAr ? 'right' : 'left' }}; }
        th { background: #f1f5f9; }
        .muted { color: #64748b; font-size: 11px; }
        .kpis td { border: none; padding: 4px 0; }
    </style>
</head>
<body>
    <p class="muted">{{ $t['org'] }}</p>
    <h1>{{ $t['title'] }}</h1>
    <table class="kpis">
        <tr><td>{{ $t['name'] }}</td><td>{{ $student['full_name'] ?? $t['empty'] }}</td></tr>
        <tr><td>{{ $t['level'] }}</td><td>{{ $levelName }}</td></tr>
        <tr><td>{{ $t['year'] }}</td><td>{{ $student['academic_year']['name'] ?? $t['empty'] }}</td></tr>
        <tr><td>{{ $t['period'] }}</td><td>{{ $periodLabel }}</td></tr>
        <tr><td>{{ $t['average'] }}</td><td>{{ $report['overall_average'] ?? $t['empty'] }}</td></tr>
        <tr><td>{{ $t['classAverage'] }}</td><td>{{ $report['class_average'] ?? $t['empty'] }}</td></tr>
        <tr><td>{{ $t['previous'] }}</td><td>{{ $report['previous_average'] ?? $t['empty'] }}</td></tr>
    </table>

    <h2>{{ $t['subjects'] }}</h2>
    @foreach(($report['subjects'] ?? []) as $subjectRow)
        @php
            $sub = $subjectRow['subject'] ?? [];
            $subName = $isAr ? ($sub['name_ar'] ?? '') : ($sub['name_fr'] ?? $sub['name_ar'] ?? '');
        @endphp
        <p><strong>{{ $subName }}</strong> — {{ $t['average'] }}: {{ $subjectRow['average'] ?? $t['empty'] }}</p>
        <table>
            <thead>
                <tr>
                    <th>{{ $t['exam'] }}</th>
                    <th>{{ $t['score'] }}</th>
                    <th>{{ $t['max'] }}</th>
                    <th>{{ $t['percent'] }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($subjectRow['exams'] ?? []) as $exam)
                    <tr>
                        <td>{{ $exam['title'] }}</td>
                        <td>{{ $exam['is_absent'] ? $t['empty'] : ($exam['score'] ?? $t['empty']) }}</td>
                        <td>{{ $exam['max_score'] ?? $t['empty'] }}</td>
                        <td>{{ $exam['percent'] ?? $t['empty'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">{{ $t['empty'] }}</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    <h2>{{ $t['periods'] }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ $t['year'] }}</th>
                <th>{{ $t['period'] }}</th>
                <th>{{ $t['average'] }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($report['periods'] ?? []) as $row)
                <tr>
                    <td>{{ $row['year_name'] ?? $t['empty'] }}</td>
                    <td>{{ $t[$row['period']] ?? $row['period'] }}</td>
                    <td>{{ $row['average'] ?? $t['empty'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ $t['empty'] }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="muted" style="margin-top:24px">{{ $t['footer'] }}</p>
</body>
</html>
