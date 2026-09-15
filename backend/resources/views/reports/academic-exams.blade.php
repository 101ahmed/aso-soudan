@php
    $isAr = ($locale ?? 'ar') === 'ar';
    $dir = $isAr ? 'rtl' : 'ltr';
    $t = [
        'ar' => [
            'org' => 'الرابطة السودانية برين',
            'title' => 'قائمة الامتحانات',
            'exam' => 'الامتحان',
            'level' => 'المستوى',
            'subject' => 'المادة',
            'date' => 'التاريخ',
            'period' => 'الفترة',
            'max' => 'الدرجة الكاملة',
            'pass' => 'درجة النجاح',
            'empty' => 'لا توجد امتحانات',
            'footer' => 'وثيقة داخلية — الرابطة السودانية برين',
            'term1' => 'الفصل الأول',
            'term2' => 'الفصل الثاني',
            'term3' => 'الفصل الثالث',
            'annual' => 'سنوي',
        ],
        'fr' => [
            'org' => 'Association soudanaise de Rennes',
            'title' => 'Liste des examens',
            'exam' => 'Examen',
            'level' => 'Niveau',
            'subject' => 'Matière',
            'date' => 'Date',
            'period' => 'Période',
            'max' => 'Note max.',
            'pass' => 'Seuil',
            'empty' => 'Aucun examen',
            'footer' => 'Document interne — Association soudanaise de Rennes',
            'term1' => '1er trimestre',
            'term2' => '2e trimestre',
            'term3' => '3e trimestre',
            'annual' => 'Annuel',
        ],
        'en' => [
            'org' => 'Sudanese Association of Rennes',
            'title' => 'Exam list',
            'exam' => 'Exam',
            'level' => 'Level',
            'subject' => 'Subject',
            'date' => 'Date',
            'period' => 'Period',
            'max' => 'Max',
            'pass' => 'Pass',
            'empty' => 'No exams',
            'footer' => 'Internal document — Sudanese Association of Rennes',
            'term1' => 'Term 1',
            'term2' => 'Term 2',
            'term3' => 'Term 3',
            'annual' => 'Annual',
        ],
    ][$locale ?? 'ar'] ?? [];
    $name = function ($item) use ($isAr) {
        if (! $item) {
            return '—';
        }
        $arr = is_array($item) ? $item : $item->toArray();

        return $isAr
            ? ($arr['name_ar'] ?? $arr['name'] ?? '—')
            : ($arr['name_fr'] ?? $arr['name_ar'] ?? $arr['name'] ?? '—');
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
    </style>
</head>
<body>
    <p class="muted">{{ $t['org'] }}</p>
    <h1>{{ $t['title'] }}</h1>
    <table>
        <thead>
            <tr>
                <th>{{ $t['exam'] }}</th>
                <th>{{ $t['level'] }}</th>
                <th>{{ $t['subject'] }}</th>
                <th>{{ $t['period'] }}</th>
                <th>{{ $t['date'] }}</th>
                <th>{{ $t['max'] }}</th>
                <th>{{ $t['pass'] }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($exams as $exam)
                <tr>
                    <td>{{ $exam->title }}</td>
                    <td>{{ $name($exam->level) }}</td>
                    <td>{{ $name($exam->subject) }}</td>
                    <td>{{ $t[$exam->period] ?? $exam->period }}</td>
                    <td>{{ $exam->exam_date?->toDateString() }}</td>
                    <td>{{ $exam->max_score }}</td>
                    <td>{{ $exam->pass_score }}</td>
                </tr>
            @empty
                <tr><td colspan="7">{{ $t['empty'] }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="muted" style="margin-top:24px">{{ $t['footer'] }}</p>
</body>
</html>
