@php
    $isAr = ($locale ?? 'ar') === 'ar';
    $dir = $isAr ? 'rtl' : 'ltr';
    $t = [
        'ar' => [
            'org' => 'رابطة الجالية السودانية برين',
            'title' => 'جدول الحضور والغياب',
            'period' => 'الفترة',
            'level' => 'المستوى',
            'allLevels' => 'كل المستويات',
            'student' => 'الطالب',
            'empty' => 'لا يوجد طلاب لهذه الفترة.',
            'totals' => 'المجموع حاضر / غائب',
            'present' => 'حاضر',
            'absent' => 'غائب',
            'late' => 'متأخر',
            'excused' => 'معذور',
            'footer' => 'وثيقة داخلية — رابطة الجالية السودانية برين',
            'generated' => 'تاريخ الإصدار',
            'marks' => ['present' => 'ح', 'absent' => 'غ', 'late' => 'ت', 'excused' => 'ع'],
        ],
        'fr' => [
            'org' => 'Association de la communauté soudanaise de Rennes',
            'title' => 'Tableau des présences',
            'period' => 'Période',
            'level' => 'Niveau',
            'allLevels' => 'Tous les niveaux',
            'student' => 'Élève',
            'empty' => 'Aucun élève pour cette période.',
            'totals' => 'Total présents / absents',
            'present' => 'Présent',
            'absent' => 'Absent',
            'late' => 'Retard',
            'excused' => 'Excusé',
            'footer' => 'Document interne — Association de la communauté soudanaise de Rennes',
            'generated' => 'Émis le',
            'marks' => ['present' => 'P', 'absent' => 'A', 'late' => 'R', 'excused' => 'E'],
        ],
        'en' => [
            'org' => 'Sudanese Community Association of Rennes',
            'title' => 'Attendance register',
            'period' => 'Period',
            'level' => 'Level',
            'allLevels' => 'All levels',
            'student' => 'Student',
            'empty' => 'No students for this period.',
            'totals' => 'Total present / absent',
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'excused' => 'Excused',
            'footer' => 'Internal document — Sudanese Community Association of Rennes',
            'generated' => 'Issued',
            'marks' => ['present' => 'P', 'absent' => 'A', 'late' => 'R', 'excused' => 'E'],
        ],
    ][$locale] ?? [];
    $label = fn ($key) => $t[$key] ?? $key;
    $mark = fn ($status) => $t['marks'][$status] ?? '—';
    $levelOf = function ($student) use ($isAr) {
        $level = $student['level'] ?? null;
        if (! $level) {
            return '—';
        }
        return $isAr ? ($level['name_ar'] ?: $level['name_fr']) : ($level['name_fr'] ?: $level['name_ar']);
    };
    $statusOf = fn ($student, $iso) => $student['days'][$iso]['status'] ?? '';
    $count = function ($iso, $status) use ($payload) {
        $n = 0;
        foreach ($payload['students'] as $student) {
            if (($student['days'][$iso]['status'] ?? '') === $status) {
                $n++;
            }
        }
        return $n;
    };
    $fromLabel = \Carbon\Carbon::parse($payload['from'])->format('d/m/Y');
    $toLabel = \Carbon\Carbon::parse($payload['to'])->format('d/m/Y');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ $label('title') }}</title>
    <style>
        body { font-family: DejaVu Sans, Tahoma, Arial, sans-serif; color: #1e293b; font-size: 11px; margin: 18px; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #134e4a; }
        .muted { color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: center; }
        th { background: #f1f5f9; font-weight: bold; }
        td.name, th.name { text-align: start; }
        .present { color: #047857; font-weight: bold; }
        .absent { color: #b91c1c; font-weight: bold; }
        .late { color: #b45309; font-weight: bold; }
        .excused { color: #0369a1; font-weight: bold; }
        .footer { margin-top: 18px; font-size: 9px; color: #64748b; }
        .legend span { margin-inline-end: 12px; }
    </style>
</head>
<body>
    <p class="muted">{{ $label('org') }}</p>
    <h1>{{ $label('title') }}</h1>
    <p class="muted">
        {{ $label('period') }}: {{ $fromLabel }} — {{ $toLabel }}
        · {{ $label('level') }}: {{ $levelName ?: $label('allLevels') }}
        · {{ $label('generated') }} {{ now()->format('Y-m-d H:i') }}
    </p>
    <p class="legend muted">
        <span class="present">{{ $t['marks']['present'] }} {{ $label('present') }}</span>
        <span class="absent">{{ $t['marks']['absent'] }} {{ $label('absent') }}</span>
        <span class="late">{{ $t['marks']['late'] }} {{ $label('late') }}</span>
        <span class="excused">{{ $t['marks']['excused'] }} {{ $label('excused') }}</span>
    </p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th class="name">{{ $label('student') }}</th>
                <th>{{ $label('level') }}</th>
                @foreach($days as $day)
                    <th>{{ $day['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($payload['students'] as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="name">{{ $student['full_name'] }}</td>
                    <td>{{ $levelOf($student) }}</td>
                    @foreach($days as $day)
                        @php $st = $statusOf($student, $day['iso']); @endphp
                        <td class="{{ $st }}">{{ $st ? $mark($st) : '—' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 3 + count($days) }}">{{ $label('empty') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($payload['students']))
            <tfoot>
                <tr>
                    <th colspan="3" class="name">{{ $label('totals') }}</th>
                    @foreach($days as $day)
                        <th>
                            <span class="present">{{ $count($day['iso'], 'present') }}</span>
                            /
                            <span class="absent">{{ $count($day['iso'], 'absent') }}</span>
                        </th>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
    <p class="footer">{{ $label('footer') }}</p>
</body>
</html>
