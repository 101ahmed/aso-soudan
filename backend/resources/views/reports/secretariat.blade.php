@php
    $isAr = ($locale ?? 'ar') === 'ar';
    $dir = $isAr ? 'rtl' : 'ltr';
    $dept = $report['department'];
    $activity = $report['activity'];
    $specific = $report['specific'] ?? [];
    $name = $isAr ? ($dept['name_ar'] ?: $dept['name_fr']) : ($dept['name_fr'] ?: $dept['name_ar']);
    $officer = $isAr ? ($dept['officer_name_ar'] ?: $dept['officer_name_fr']) : ($dept['officer_name_fr'] ?: $dept['officer_name_ar']);
    $deputy = $isAr ? ($dept['deputy_name_ar'] ?: $dept['deputy_name_fr']) : ($dept['deputy_name_fr'] ?: $dept['deputy_name_ar']);
    $t = [
        'ar' => [
            'title' => 'تقرير الأمانة',
            'org' => 'رابطة الجالية السودانية برين',
            'year' => 'السنة',
            'generated' => 'تاريخ الإصدار',
            'officer' => 'الأمين',
            'deputy' => 'نائب الأمين',
            'activity' => 'نشاط الأمانة',
            'news' => 'الأخبار',
            'newsPublished' => 'أخبار منشورة',
            'events' => 'الفعاليات',
            'eventsPublished' => 'فعاليات منشورة',
            'announcements' => 'الإعلانات',
            'albums' => 'الألبومات',
            'messages' => 'الرسائل',
            'messagesNew' => 'رسائل جديدة',
            'recentNews' => 'آخر الأخبار',
            'recentEvents' => 'آخر الفعاليات',
            'academic' => 'الحصيلة الأكاديمية',
            'students' => 'الطلاب',
            'studentsActive' => 'طلاب نشطون',
            'teachers' => 'المعلمون',
            'attendanceRate' => 'نسبة الحضور',
            'present' => 'حاضر',
            'absent' => 'غائب',
            'late' => 'متأخر',
            'excused' => 'معذور',
            'social' => 'طلبات المساعدة',
            'finance' => 'الحصيلة المالية',
            'budget' => 'الميزانية المعتمدة',
            'revenues' => 'الإيرادات',
            'expenses' => 'المصروفات',
            'balance' => 'الرصيد',
            'remaining' => 'المتبقي من الميزانية',
            'media' => 'الحصيلة الإعلامية',
            'decisions' => 'القرارات والتوجيهات',
            'press' => 'مركز الإعلام',
            'pressPublished' => 'منشورات صادرة',
            'members' => 'الأعضاء',
            'partners' => 'الشركاء',
            'documents' => 'الوثائق',
            'contacts' => 'طلبات التواصل',
            'empty' => 'لا توجد بيانات لهذه السنة.',
            'footer' => 'وثيقة داخلية — رابطة الجالية السودانية برين',
        ],
        'fr' => [
            'title' => 'Rapport du secrétariat',
            'org' => 'Association de la communauté soudanaise de Rennes',
            'year' => 'Année',
            'generated' => 'Émis le',
            'officer' => 'Secrétaire',
            'deputy' => 'Adjoint',
            'activity' => 'Activité du secrétariat',
            'news' => 'Actualités',
            'newsPublished' => 'Actualités publiées',
            'events' => 'Événements',
            'eventsPublished' => 'Événements publiés',
            'announcements' => 'Annonces',
            'albums' => 'Albums',
            'messages' => 'Messages',
            'messagesNew' => 'Nouveaux messages',
            'recentNews' => 'Dernières actualités',
            'recentEvents' => 'Derniers événements',
            'academic' => 'Bilan académique',
            'students' => 'Élèves',
            'studentsActive' => 'Élèves actifs',
            'teachers' => 'Enseignants',
            'attendanceRate' => 'Taux de présence',
            'present' => 'Présents',
            'absent' => 'Absents',
            'late' => 'Retards',
            'excused' => 'Excusés',
            'social' => 'Demandes d’aide',
            'finance' => 'Bilan financier',
            'budget' => 'Budget approuvé',
            'revenues' => 'Recettes',
            'expenses' => 'Dépenses',
            'balance' => 'Solde',
            'remaining' => 'Reste du budget',
            'media' => 'Bilan médias',
            'decisions' => 'Décisions et directives',
            'press' => 'Centre médias',
            'pressPublished' => 'Publications parues',
            'members' => 'Adhérents',
            'partners' => 'Partenaires',
            'documents' => 'Documents',
            'contacts' => 'Demandes de contact',
            'empty' => 'Aucune donnée pour cette année.',
            'footer' => 'Document interne — Association de la communauté soudanaise de Rennes',
        ],
        'en' => [
            'title' => 'Secretariat report',
            'org' => 'Sudanese community association of Rennes',
            'year' => 'Year',
            'generated' => 'Issued on',
            'officer' => 'Officer',
            'deputy' => 'Deputy',
            'activity' => 'Secretariat activity',
            'news' => 'News',
            'newsPublished' => 'Published news',
            'events' => 'Events',
            'eventsPublished' => 'Published events',
            'announcements' => 'Announcements',
            'albums' => 'Albums',
            'messages' => 'Messages',
            'messagesNew' => 'New messages',
            'recentNews' => 'Latest news',
            'recentEvents' => 'Latest events',
            'academic' => 'Academic summary',
            'students' => 'Students',
            'studentsActive' => 'Active students',
            'teachers' => 'Teachers',
            'attendanceRate' => 'Attendance rate',
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'excused' => 'Excused',
            'social' => 'Help requests',
            'finance' => 'Financial summary',
            'budget' => 'Approved budget',
            'revenues' => 'Revenues',
            'expenses' => 'Expenses',
            'balance' => 'Balance',
            'remaining' => 'Budget remaining',
            'media' => 'Media summary',
            'decisions' => 'Decisions and directives',
            'press' => 'Media center',
            'pressPublished' => 'Published items',
            'members' => 'Members',
            'partners' => 'Partners',
            'documents' => 'Documents',
            'contacts' => 'Contact requests',
            'empty' => 'No data for this year.',
            'footer' => 'Internal document — Sudanese community association of Rennes',
        ],
    ][$locale ?? 'ar'] ?? [];
    $label = fn ($key) => $t[$key] ?? $key;
    $titleOf = function ($item) use ($isAr) {
        return $isAr ? ($item['title_ar'] ?? $item['title_fr'] ?? '') : ($item['title_fr'] ?? $item['title_ar'] ?? '');
    };
    $money = fn ($n) => number_format((float) $n, 2, ',', ' ');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ $label('title') }} — {{ $name }} — {{ $report['year'] }}</title>
    <style>
        body { font-family: DejaVu Sans, Tahoma, Arial, sans-serif; color: #1e293b; font-size: 12px; margin: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #134e4a; }
        h2 { font-size: 14px; margin: 18px 0 8px; color: #134e4a; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        .muted { color: #64748b; font-size: 11px; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .grid td, .grid th { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: start; }
        .grid th { background: #f1f5f9; }
        .kpi { display: inline-block; width: 30%; margin: 0 1% 8px 0; border: 1px solid #e2e8f0; padding: 8px; vertical-align: top; }
        .kpi b { display: block; font-size: 16px; color: #134e4a; }
        ul { padding-inline-start: 18px; }
        .footer { margin-top: 28px; font-size: 10px; color: #64748b; }
    </style>
</head>
<body>
    <p class="muted">{{ $label('org') }}</p>
    <h1>{{ $label('title') }}: {{ $name }}</h1>
    <p class="muted">
        {{ $label('year') }} {{ $report['year'] }}
        · {{ $label('generated') }} {{ \Carbon\Carbon::parse($report['generated_at'])->format('Y-m-d H:i') }}
    </p>
    <p>
        @if($officer) {{ $label('officer') }}: <strong>{{ $officer }}</strong> @endif
        @if($deputy) · {{ $label('deputy') }}: <strong>{{ $deputy }}</strong> @endif
    </p>

    <h2>{{ $label('activity') }}</h2>
    <div class="kpi"><span>{{ $label('news') }}</span><b>{{ $activity['news_total'] }}</b></div>
    <div class="kpi"><span>{{ $label('newsPublished') }}</span><b>{{ $activity['news_published'] }}</b></div>
    <div class="kpi"><span>{{ $label('events') }}</span><b>{{ $activity['events_total'] }}</b></div>
    <div class="kpi"><span>{{ $label('eventsPublished') }}</span><b>{{ $activity['events_published'] }}</b></div>
    <div class="kpi"><span>{{ $label('announcements') }}</span><b>{{ $activity['announcements_total'] }}</b></div>
    <div class="kpi"><span>{{ $label('albums') }}</span><b>{{ $activity['albums_total'] }}</b></div>
    <div class="kpi"><span>{{ $label('messages') }}</span><b>{{ $activity['messages_total'] }}</b></div>
    <div class="kpi"><span>{{ $label('messagesNew') }}</span><b>{{ $activity['messages_new'] }}</b></div>

    @if(($specific['kind'] ?? '') === 'academic')
        <h2>{{ $label('academic') }}</h2>
        <div class="kpi"><span>{{ $label('students') }}</span><b>{{ $specific['students_total'] }}</b></div>
        <div class="kpi"><span>{{ $label('studentsActive') }}</span><b>{{ $specific['students_active'] }}</b></div>
        <div class="kpi"><span>{{ $label('teachers') }}</span><b>{{ $specific['teachers_active'] }}</b></div>
        <div class="kpi"><span>{{ $label('attendanceRate') }}</span><b>{{ $specific['attendance_rate'] !== null ? $specific['attendance_rate'].'%' : '—' }}</b></div>
        <div class="kpi"><span>{{ $label('present') }}</span><b>{{ $specific['attendance_present'] }}</b></div>
        <div class="kpi"><span>{{ $label('absent') }}</span><b>{{ $specific['attendance_absent'] }}</b></div>
    @elseif(($specific['kind'] ?? '') === 'social')
        <h2>{{ $label('social') }}</h2>
        <div class="kpi"><span>{{ $label('social') }}</span><b>{{ $specific['total'] }}</b></div>
        <table class="grid">
            <tr><th>Status</th><th>Total</th></tr>
            @forelse($specific['by_status'] as $row)
                <tr><td>{{ $row['key'] }}</td><td>{{ $row['total'] }}</td></tr>
            @empty
                <tr><td colspan="2">{{ $label('empty') }}</td></tr>
            @endforelse
        </table>
    @elseif(($specific['kind'] ?? '') === 'finance')
        <h2>{{ $label('finance') }}</h2>
        <div class="kpi"><span>{{ $label('budget') }}</span><b>{{ $money($specific['approved_budget']) }}</b></div>
        <div class="kpi"><span>{{ $label('revenues') }}</span><b>{{ $money($specific['total_revenues']) }}</b></div>
        <div class="kpi"><span>{{ $label('expenses') }}</span><b>{{ $money($specific['total_expenses']) }}</b></div>
        <div class="kpi"><span>{{ $label('balance') }}</span><b>{{ $money($specific['balance']) }}</b></div>
        <div class="kpi"><span>{{ $label('remaining') }}</span><b>{{ $money($specific['remaining_budget']) }}</b></div>
    @elseif(($specific['kind'] ?? '') === 'media')
        <h2>{{ $label('media') }}</h2>
        <div class="kpi"><span>{{ $label('decisions') }}</span><b>{{ $specific['decisions_total'] }}</b></div>
        <div class="kpi"><span>{{ $label('press') }}</span><b>{{ $specific['press_total'] }}</b></div>
        <div class="kpi"><span>{{ $label('pressPublished') }}</span><b>{{ $specific['press_published'] }}</b></div>
    @elseif(($specific['kind'] ?? '') === 'statistics')
        <h2>{{ $label('members') }}</h2>
        <div class="kpi"><span>{{ $label('members') }}</span><b>{{ $specific['members_total'] }}</b></div>
    @elseif(($specific['kind'] ?? '') === 'external')
        <h2>{{ $label('partners') }}</h2>
        <div class="kpi"><span>{{ $label('partners') }}</span><b>{{ $specific['partners_total'] }}</b></div>
        <div class="kpi"><span>{{ $label('documents') }}</span><b>{{ $specific['documents_total'] }}</b></div>
        <div class="kpi"><span>{{ $label('contacts') }}</span><b>{{ $specific['contacts_total'] }}</b></div>
    @endif

    <h2>{{ $label('recentNews') }}</h2>
    <ul>
        @forelse($activity['recent_news'] as $item)
            <li>{{ $titleOf($item) }} — {{ $item['date'] ?: '—' }}</li>
        @empty
            <li>{{ $label('empty') }}</li>
        @endforelse
    </ul>

    <h2>{{ $label('recentEvents') }}</h2>
    <ul>
        @forelse($activity['recent_events'] as $item)
            <li>{{ $titleOf($item) }} — {{ $item['date'] ?: '—' }}</li>
        @empty
            <li>{{ $label('empty') }}</li>
        @endforelse
    </ul>

    <p class="footer">{{ $label('footer') }}</p>
</body>
</html>
