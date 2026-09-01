@php
    $isAr = ($locale ?? 'ar') === 'ar';
    // Dompdf has no OpenType shaping: Arabic is converted to visual glyphs in the controller.
    // Keep dir=ltr so those glyphs are not reversed a second time.
    $dir = 'ltr';
    $align = $isAr ? 'right' : 'left';
    $t = [
        'ar' => [
            'org' => 'الرابطة السودانية برين',
            'title' => 'جدول الحضور والغياب',
            'period' => 'الفترة',
            'level' => 'المستوى',
            'allLevels' => 'كل المستويات',
            'student' => 'الطالب',
            'empty' => 'لا يوجد طلاب لهذه الفترة.',
            'totals' => 'المجموع حاضر / غائب',
            'present' => 'حاضر',
            'absent' => 'غائب',
            'footer' => 'وثيقة داخلية — الرابطة السودانية برين',
            'generated' => 'تاريخ الإصدار',
            'marks' => ['present' => 'ح', 'absent' => 'غ', 'late' => 'ت', 'excused' => 'ع'],
        ],
        'fr' => [
            'org' => 'Association soudanaise de Rennes',
            'title' => 'Tableau des présences',
            'period' => 'Période',
            'level' => 'Niveau',
            'allLevels' => 'Tous les niveaux',
            'student' => 'Élève',
            'empty' => 'Aucun élève pour cette période.',
            'totals' => 'Total présents / absents',
            'present' => 'Présent',
            'absent' => 'Absent',
            'footer' => 'Document interne — Association soudanaise de Rennes',
            'generated' => 'Émis le',
            'marks' => ['present' => 'P', 'absent' => 'A', 'late' => 'R', 'excused' => 'E'],
        ],
        'en' => [
            'org' => 'Sudanese Association of Rennes',
            'title' => 'Attendance register',
            'period' => 'Period',
            'level' => 'Level',
            'allLevels' => 'All levels',
            'student' => 'Student',
            'empty' => 'No students for this period.',
            'totals' => 'Total present / absent',
            'present' => 'Present',
            'absent' => 'Absent',
            'footer' => 'Internal document — Sudanese Association of Rennes',
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
    $periodText = $fromLabel === $toLabel ? $fromLabel : $fromLabel.' — '.$toLabel;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ $label('title') }}</title>
    <style>
        body { font-family: DejaVu Sans, Tahoma, Arial, sans-serif; color: #1e293b; font-size: 11px; margin: 18px; text-align: {{ $align }}; direction: ltr; unicode-bidi: bidi-override; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #134e4a; }
        .muted { color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; direction: ltr; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: center; }
        th { background: #f1f5f9; font-weight: bold; }
        td.name, th.name { text-align: {{ $align }}; }
        .present { color: #047857; font-weight: bold; }
        .absent { color: #b91c1c; font-weight: bold; }
        .late { color: #b45309; font-weight: bold; }
        .excused { color: #0369a1; font-weight: bold; }
        .footer { margin-top: 18px; font-size: 9px; color: #64748b; }
        .legend span { margin: 0 10px; }
    </style>
</head>
<body>
    <p class="muted">{{ $label('org') }}</p>
    <h1>{{ $label('title') }}</h1>
    <p class="muted">
        <span>{{ $label('period') }}</span>
        {{ $periodText }}
        ·
        <span>{{ $label('level') }}</span>
        {{ $levelName ?: $label('allLevels') }}
        ·
        <span>{{ $label('generated') }}</span>
        {{ now()->format('Y-m-d H:i') }}
    </p>
    <p class="legend muted">
        <span class="present"><span>{{ $t['marks']['present'] }}</span> <span>{{ $label('present') }}</span></span>
        <span class="absent"><span>{{ $t['marks']['absent'] }}</span> <span>{{ $label('absent') }}</span></span>
    </p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th class="name">{{ $label('student') }}</th>
                <th>{{ $label('level') }}</th>
                @foreach($days as $day)
                    <th>
                        <div>{{ $day['weekday'] ?? $day['label'] }}</div>
                        <div>{{ $day['date'] ?? '' }}</div>
                    </th>
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
