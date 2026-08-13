import { secretariats } from './secretariats'

export const orgUnits = [
  { slug: 'president', nameKey: 'org.president', path: '/president' },
  ...secretariats.map((item) => ({
    slug: item.slug,
    nameKey: item.nameKey,
    path: `/secretariats/${item.slug}`,
  })),
  { slug: 'shura', nameKey: 'org.shura', path: '/shura-council' },
  { slug: 'parents', nameKey: 'org.parents', path: '/parents-council' },
]

export const publicStats = [
  { key: 'members', value: '+250' },
  { key: 'students', value: '+120' },
  { key: 'teachers', value: '+15' },
  { key: 'events', value: '+35' },
  { key: 'initiatives', value: '+20' },
]

export const newsItems = [
  {
    id: 1,
    slug: 'rentree-academique-2026',
    secretariat: 'academic',
    image: 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1200&q=80',
    date: '2026-08-01',
    title: { ar: 'انطلاق السنة الأكاديمية 2026/2027', fr: 'Lancement de l’année académique 2026/2027', en: 'Launch of the 2026/2027 academic year' },
    excerpt: {
      ar: 'افتتاح التسجيلات الدراسية وأنشطة الدعم اللغوي والثقافي.',
      fr: 'Ouverture des inscriptions scolaires et des activités de soutien linguistique et culturel.',
      en: 'Opening of school enrolment and language and cultural support activities.',
    },
  },
  {
    id: 2,
    slug: 'ramadan-solidarite',
    secretariat: 'social',
    image: 'https://images.unsplash.com/photo-1460518451285-97b6d51863b6?auto=format&fit=crop&w=1200&q=80',
    date: '2026-03-20',
    title: { ar: 'حملة تضامنية رمضانية', fr: 'Campagne de solidarité du Ramadan', en: 'Ramadan solidarity campaign' },
    excerpt: {
      ar: 'مبادرة اجتماعية لدعم الأسر داخل الجالية في مدينة رين.',
      fr: 'Une initiative sociale pour soutenir les familles de la communauté à Rennes.',
      en: 'A social initiative to support families in the community in Rennes.',
    },
  },
  {
    id: 3,
    slug: 'journee-culturelle',
    secretariat: 'media',
    image: 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?auto=format&fit=crop&w=1200&q=80',
    date: '2026-05-12',
    title: { ar: 'يوم ثقافي سوداني في رين', fr: 'Journée culturelle soudanaise à Rennes', en: 'Sudanese cultural day in Rennes' },
    excerpt: {
      ar: 'احتفاء بالتراث والفنون والتعارف بين أفراد الجالية.',
      fr: 'Célébration du patrimoine, des arts et des rencontres communautaires.',
      en: 'A celebration of heritage, arts and community gatherings.',
    },
  },
  {
    id: 4,
    slug: 'programme-enfants-ete',
    secretariat: 'women-children',
    image: 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=1200&q=80',
    date: '2026-07-05',
    title: { ar: 'انطلاق البرنامج الصيفي للأطفال', fr: 'Lancement du programme d’été pour enfants', en: 'Launch of the summer programme for children' },
    excerpt: {
      ar: 'أنشطة تربوية وترفيهية منظمة ضمن شؤون المرأة والطفل.',
      fr: 'Activités éducatives et ludiques organisées par Femmes & Enfants.',
      en: 'Educational and recreational activities organised by Women & Children.',
    },
  },
]

export const upcomingEvents = [
  {
    id: 1,
    slug: 'assemblee-generale-2026',
    secretariat: 'general',
    image: 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1200&q=80',
    title: { ar: 'الجمعية العمومية السنوية', fr: 'Assemblée générale annuelle', en: 'Annual general meeting' },
    date: '2026-09-14',
    time: '15:00',
    place: { ar: 'رين — قاعة الاجتماعات', fr: 'Rennes — salle de réunion', en: 'Rennes — meeting room' },
    organizer: { ar: 'الأمانة العامة', fr: 'Secrétariat général', en: 'General Secretariat' },
    summary: {
      ar: 'عرض حصيلة السنة وخطة العمل للمرحلة القادمة.',
      fr: 'Bilan de l’année et présentation du plan d’action à venir.',
      en: 'Review of the year and presentation of the upcoming action plan.',
    },
    registrationOpen: true,
  },
  {
    id: 2,
    slug: 'atelier-arabe',
    secretariat: 'academic',
    image: 'https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=1200&q=80',
    title: { ar: 'ورشة دعم اللغة العربية', fr: 'Atelier de soutien en langue arabe', en: 'Arabic language support workshop' },
    date: '2026-09-21',
    time: '10:00',
    place: { ar: 'المقر الأكاديمي', fr: 'Siège académique', en: 'Academic premises' },
    organizer: { ar: 'الأمانة الأكاديمية', fr: 'Secrétariat académique', en: 'Academic Secretariat' },
    summary: {
      ar: 'جلسة تعليمية مفتوحة للطلاب وأولياء الأمور.',
      fr: 'Session pédagogique ouverte aux élèves et aux parents.',
      en: 'An educational session open to students and parents.',
    },
    registrationOpen: true,
  },
  {
    id: 3,
    slug: 'rencontre-partenaires',
    secretariat: 'external-relations',
    image: 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1200&q=80',
    title: { ar: 'لقاء مع شركاء محليين', fr: 'Rencontre avec des partenaires locaux', en: 'Meeting with local partners' },
    date: '2026-10-03',
    time: '17:00',
    place: { ar: 'رين', fr: 'Rennes', en: 'Rennes' },
    organizer: { ar: 'الأمانة الخارجية', fr: 'Relations extérieures', en: 'External Relations' },
    summary: {
      ar: 'تنسيق التعاون مع الجمعيات والمؤسسات المحلية.',
      fr: 'Coordination de la coopération avec associations et institutions locales.',
      en: 'Coordinating cooperation with local associations and institutions.',
    },
    registrationOpen: false,
  },
]

export const recentActivities = [
  { key: 'academic', image: 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=900&q=80' },
  { key: 'social', image: 'https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?auto=format&fit=crop&w=900&q=80' },
  { key: 'womenChildren', image: 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=900&q=80' },
  { key: 'cultural', image: 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=900&q=80' },
]

export const galleryAlbums = [
  {
    slug: 'ramadan-2026',
    secretariat: 'social',
    cover: 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1000&q=80',
    title: { ar: 'فعاليات رمضان 2026', fr: 'Événements du Ramadan 2026', en: 'Ramadan 2026 events' },
  },
  {
    slug: 'academic-activities',
    secretariat: 'academic',
    cover: 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=1000&q=80',
    title: { ar: 'الأنشطة الأكاديمية', fr: 'Activités académiques', en: 'Academic activities' },
  },
  {
    slug: 'children',
    secretariat: 'women-children',
    cover: 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=1000&q=80',
    title: { ar: 'فعاليات الأطفال', fr: 'Activités enfants', en: 'Children’s activities' },
  },
  {
    slug: 'social',
    secretariat: 'social',
    cover: 'https://images.unsplash.com/photo-1559027615-cd4628902d4a?auto=format&fit=crop&w=1000&q=80',
    title: { ar: 'الأنشطة الاجتماعية', fr: 'Activités sociales', en: 'Social activities' },
  },
  {
    slug: 'media-coverage',
    secretariat: 'media',
    cover: 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1000&q=80',
    title: { ar: 'تغطيات إعلامية', fr: 'Couvertures médiatiques', en: 'Media coverage' },
  },
]

export function newsBySecretariat(slug) {
  return newsItems.filter((item) => item.secretariat === slug)
}

export function eventsBySecretariat(slug) {
  return upcomingEvents.filter((item) => item.secretariat === slug)
}

export function albumsBySecretariat(slug) {
  return galleryAlbums.filter((item) => item.secretariat === slug)
}
