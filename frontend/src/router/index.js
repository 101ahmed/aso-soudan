import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { resolveAdminEntryPath, rememberAdminPath, resolvePostLoginPath } from '@/utils/roleRedirect'
import PublicLayout from '@/layouts/PublicLayout.vue'
import HomeView from '@/views/HomeView.vue'
import StatusView from '@/views/StatusView.vue'
import SecretariatsView from '@/views/public/SecretariatsView.vue'
import SecretariatDetailView from '@/views/public/SecretariatDetailView.vue'
import ShuraCouncilView from '@/views/public/ShuraCouncilView.vue'
import ParentsCouncilView from '@/views/public/ParentsCouncilView.vue'
import AboutView from '@/views/public/AboutView.vue'
import PresidentView from '@/views/public/PresidentView.vue'
import PrivacyPolicyView from '@/views/public/PrivacyPolicyView.vue'
import NewsListView from '@/views/public/NewsListView.vue'
import NewsDetailView from '@/views/public/NewsDetailView.vue'
import MediaCenterListView from '@/views/public/MediaCenterListView.vue'
import MediaCenterDetailView from '@/views/public/MediaCenterDetailView.vue'
import EventsListView from '@/views/public/EventsListView.vue'
import EventDetailView from '@/views/public/EventDetailView.vue'
import GalleryView from '@/views/public/GalleryView.vue'
import GalleryAlbumView from '@/views/public/GalleryAlbumView.vue'
import ContactView from '@/views/public/ContactView.vue'
import StudentRegisterView from '@/views/public/StudentRegisterView.vue'
import MemberRegisterView from '@/views/public/MemberRegisterView.vue'
import HelpRequestView from '@/views/public/HelpRequestView.vue'
import SportsTeamPublicView from '@/views/public/SportsTeamPublicView.vue'
import SportsNationalPublicView from '@/views/public/SportsNationalPublicView.vue'
import SportsJoinView from '@/views/public/SportsJoinView.vue'
import SubscriptionsView from '@/views/public/SubscriptionsView.vue'
import ExternalContactView from '@/views/public/ExternalContactView.vue'
import LoginView from '@/views/auth/LoginView.vue'
import ForgotPasswordView from '@/views/auth/ForgotPasswordView.vue'
import ResetPasswordView from '@/views/auth/ResetPasswordView.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import DashboardView from '@/views/admin/DashboardView.vue'
import PresidentAdminShell from '@/views/admin/president/PresidentAdminShell.vue'
import PresidentHomeView from '@/views/admin/president/PresidentHomeView.vue'
import PresidentCardView from '@/views/admin/president/PresidentCardView.vue'
import PresidentMeetingsView from '@/views/admin/president/PresidentMeetingsView.vue'
import PresidentArchiveView from '@/views/admin/president/PresidentArchiveView.vue'
import VicePresidentAdminShell from '@/views/admin/vice-president/VicePresidentAdminShell.vue'
import VicePresidentHomeView from '@/views/admin/vice-president/VicePresidentHomeView.vue'
import SecretariatAdminShell from '@/views/admin/secretariat/SecretariatAdminShell.vue'
import SecretariatHomeView from '@/views/admin/secretariat/SecretariatHomeView.vue'
import SecretariatReportView from '@/views/admin/secretariat/SecretariatReportView.vue'
import SecretariatReportsHubView from '@/views/admin/SecretariatReportsHubView.vue'
import SecretariatNewsView from '@/views/admin/secretariat/SecretariatNewsView.vue'
import SecretariatEventsView from '@/views/admin/secretariat/SecretariatEventsView.vue'
import SecretariatAnnouncementsView from '@/views/admin/secretariat/SecretariatAnnouncementsView.vue'
import SecretariatAlbumsView from '@/views/admin/secretariat/SecretariatAlbumsView.vue'
import SecretariatOfficerView from '@/views/admin/secretariat/SecretariatOfficerView.vue'
import AcademicAttendanceOverview from '@/views/admin/academic/AcademicAttendanceOverview.vue'
import AcademicSubjectAttendanceView from '@/views/admin/academic/AcademicSubjectAttendanceView.vue'
import AcademicLevelAttendanceView from '@/views/admin/academic/AcademicLevelAttendanceView.vue'
import AcademicAttendanceSheetView from '@/views/admin/academic/AcademicAttendanceSheetView.vue'
import AcademicTeachersView from '@/views/admin/academic/AcademicTeachersView.vue'
import AcademicClassStaffView from '@/views/admin/academic/AcademicClassStaffView.vue'
import AcademicStudentsView from '@/views/admin/academic/AcademicStudentsView.vue'
import AcademicStudentFileView from '@/views/admin/academic/AcademicStudentFileView.vue'
import AcademicTimetableView from '@/views/admin/academic/AcademicTimetableView.vue'
import AcademicExamsView from '@/views/admin/academic/AcademicExamsView.vue'
import AcademicExamSheetView from '@/views/admin/academic/AcademicExamSheetView.vue'
import AcademicAchievementView from '@/views/admin/academic/AcademicAchievementView.vue'
import AcademicStudentAchievementView from '@/views/admin/academic/AcademicStudentAchievementView.vue'
import StatisticsMembersView from '@/views/admin/statistics/StatisticsMembersView.vue'
import SocialHelpRequestsView from '@/views/admin/secretariat/SocialHelpRequestsView.vue'
import SocialVisitsView from '@/views/admin/secretariat/SocialVisitsView.vue'
import WomenMembersView from '@/views/admin/secretariat/WomenMembersView.vue'
import SportsTeamsView from '@/views/admin/secretariat/SportsTeamsView.vue'
import SportsTeamHubView from '@/views/admin/secretariat/SportsTeamHubView.vue'
import SportsNationalView from '@/views/admin/secretariat/SportsNationalView.vue'
import SportsJoinRequestsView from '@/views/admin/secretariat/SportsJoinRequestsView.vue'
import FinanceOverviewView from '@/views/admin/secretariat/FinanceOverviewView.vue'
import FinanceRevenuesView from '@/views/admin/secretariat/FinanceRevenuesView.vue'
import FinanceExpensesView from '@/views/admin/secretariat/FinanceExpensesView.vue'
import FinanceDocumentsView from '@/views/admin/secretariat/FinanceDocumentsView.vue'
import MediaDecisionsView from '@/views/admin/secretariat/MediaDecisionsView.vue'
import SecretariatMeetingOutputsView from '@/views/admin/secretariat/SecretariatMeetingOutputsView.vue'
import MediaCenterView from '@/views/admin/secretariat/MediaCenterView.vue'
import ExternalPartnersView from '@/views/admin/secretariat/ExternalPartnersView.vue'
import ExternalDocumentsView from '@/views/admin/secretariat/ExternalDocumentsView.vue'
import ExternalContactRequestsView from '@/views/admin/secretariat/ExternalContactRequestsView.vue'
import SecretariatMessagesView from '@/views/admin/secretariat/SecretariatMessagesView.vue'
import PresidentialDirectivesView from '@/views/admin/secretariat/PresidentialDirectivesView.vue'
import SecretariatDirectivesView from '@/views/admin/secretariat/SecretariatDirectivesView.vue'
import TeacherAdminShell from '@/views/admin/teacher/TeacherAdminShell.vue'
import TeacherHomeView from '@/views/admin/teacher/TeacherHomeView.vue'
import TeacherAttendanceRegisterView from '@/views/admin/teacher/TeacherAttendanceRegisterView.vue'
import TeacherLessonPrepView from '@/views/admin/teacher/TeacherLessonPrepView.vue'
import ShuraAdminShell from '@/views/admin/shura/ShuraAdminShell.vue'
import ShuraOverviewView from '@/views/admin/shura/ShuraOverviewView.vue'
import ShuraMembersView from '@/views/admin/shura/ShuraMembersView.vue'
import ShuraMeetingsView from '@/views/admin/shura/ShuraMeetingsView.vue'
import ParentsAdminShell from '@/views/admin/parents/ParentsAdminShell.vue'
import ParentsHomeView from '@/views/admin/parents/ParentsHomeView.vue'
import ParentsMembersView from '@/views/admin/parents/ParentsMembersView.vue'
import ParentsRegistrationsView from '@/views/admin/parents/ParentsRegistrationsView.vue'
import ParentsMeetingsView from '@/views/admin/parents/ParentsMeetingsView.vue'
import ParentsSurveysView from '@/views/admin/parents/ParentsSurveysView.vue'
import UsersView from '@/views/admin/UsersView.vue'
import UserFormView from '@/views/admin/UserFormView.vue'
import RolesView from '@/views/admin/RolesView.vue'
import ContentEditorShell from '@/views/admin/content/ContentEditorShell.vue'
import ContentEditorHomeView from '@/views/admin/content/ContentEditorHomeView.vue'
import ContentEditorNewsView from '@/views/admin/content/ContentEditorNewsView.vue'
import ContentEditorAnnouncementsView from '@/views/admin/content/ContentEditorAnnouncementsView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: PublicLayout,
      children: [
        { path: '', name: 'home', component: HomeView },
        { path: 'status', name: 'status', component: StatusView },
        {
          path: 'about',
          name: 'about',
          component: AboutView,
        },
        {
          path: 'a-propos',
          redirect: '/about',
        },
        {
          path: 'privacy-policy',
          name: 'privacy',
          component: PrivacyPolicyView,
        },
        {
          path: 'politique-confidentialite',
          redirect: '/privacy-policy',
        },
        {
          path: 'president',
          name: 'president',
          component: PresidentView,
        },
        { path: 'secretariats', name: 'secretariats', component: SecretariatsView },
        {
          path: 'secretariats/external',
          redirect: '/secretariats/external-relations',
        },
        { path: 'secretariats/:slug', name: 'secretariat.detail', component: SecretariatDetailView },
        {
          path: 'shura-council',
          name: 'shura',
          component: ShuraCouncilView,
        },
        {
          path: 'conseil-choura',
          redirect: '/shura-council',
        },
        {
          path: 'parents-council',
          name: 'parents',
          component: ParentsCouncilView,
        },
        {
          path: 'conseil-parents',
          redirect: '/parents-council',
        },
        { path: 'news', name: 'news', component: NewsListView },
        { path: 'news/:slug', name: 'news.detail', component: NewsDetailView },
        { path: 'media-center', name: 'mediaCenter', component: MediaCenterListView },
        { path: 'media-center/:slug', name: 'mediaCenter.detail', component: MediaCenterDetailView },
        { path: 'events', name: 'events', component: EventsListView },
        { path: 'events/:slug', name: 'events.detail', component: EventDetailView },
        { path: 'gallery', name: 'gallery', component: GalleryView },
        { path: 'gallery/:slug', name: 'gallery.album', component: GalleryAlbumView },
        { path: 'contact', name: 'contact', component: ContactView },
        { path: 'register/student', name: 'register.student', component: StudentRegisterView },
        { path: 'register/member', name: 'register.member', component: MemberRegisterView },
        { path: 'help-request', name: 'help.request', component: HelpRequestView },
        { path: 'sports/join', name: 'sports.join', component: SportsJoinView },
        { path: 'sports/national', name: 'sports.national', component: SportsNationalPublicView },
        { path: 'sports/teams/:teamId', name: 'sports.team', component: SportsTeamPublicView },
        { path: 'subscriptions', name: 'subscriptions', component: SubscriptionsView },
        { path: 'cotisations', redirect: '/subscriptions' },
        { path: 'external-contact', name: 'external.contact', component: ExternalContactView },
      ],
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guest: true },
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: ForgotPasswordView,
      meta: { guest: true },
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: ResetPasswordView,
      meta: { guest: true },
    },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true },
      children: [
        { path: '', name: 'admin.dashboard', component: DashboardView },
        {
          path: 'reports',
          name: 'admin.reports',
          component: SecretariatReportsHubView,
          meta: { permission: 'report.view' },
        },
        {
          path: 'content',
          component: ContentEditorShell,
          meta: { anyPermission: ['news.view', 'announcement.view'] },
          children: [
            { path: '', name: 'admin.content', component: ContentEditorHomeView },
            {
              path: 'news',
              name: 'admin.content.news',
              component: ContentEditorNewsView,
              meta: { permission: 'news.view' },
            },
            {
              path: 'announcements',
              name: 'admin.content.announcements',
              component: ContentEditorAnnouncementsView,
              meta: { permission: 'announcement.view' },
            },
          ],
        },
        {
          path: 'president',
          component: PresidentAdminShell,
          meta: { roles: ['PRESIDENT', 'VICE_PRESIDENT', 'SUPER_ADMIN'] },
          children: [
            { path: '', name: 'admin.president', component: PresidentHomeView },
            { path: 'card', name: 'admin.president.card', component: PresidentCardView },
            { path: 'meetings', name: 'admin.president.meetings', component: PresidentMeetingsView },
            { path: 'archive', name: 'admin.president.archive', component: PresidentArchiveView },
          ],
        },
        {
          path: 'vice-president',
          component: VicePresidentAdminShell,
          meta: { roles: ['VICE_PRESIDENT', 'PRESIDENT', 'SUPER_ADMIN'] },
          children: [
            { path: '', name: 'admin.vicePresident', component: VicePresidentHomeView },
            {
              path: 'card',
              name: 'admin.vicePresident.card',
              component: PresidentCardView,
              props: { office: 'vice_president' },
            },
            { path: 'meetings', name: 'admin.vicePresident.meetings', component: PresidentMeetingsView },
            { path: 'archive', name: 'admin.vicePresident.archive', component: PresidentArchiveView },
          ],
        },
        {
          path: 'secretariats/:code',
          component: SecretariatAdminShell,
          props: true,
          children: [
            { path: '', name: 'admin.secretariat', component: SecretariatHomeView },
            {
              path: 'reports',
              name: 'admin.secretariat.reports',
              component: SecretariatReportView,
              meta: { permission: 'report.view' },
            },
            { path: 'news', name: 'admin.secretariat.news', component: SecretariatNewsView },
            { path: 'events', name: 'admin.secretariat.events', component: SecretariatEventsView },
            { path: 'announcements', name: 'admin.secretariat.announcements', component: SecretariatAnnouncementsView },
            { path: 'albums', name: 'admin.secretariat.albums', component: SecretariatAlbumsView },
            { path: 'officer', name: 'admin.secretariat.officer', component: SecretariatOfficerView },
            { path: 'deputy', name: 'admin.secretariat.deputy', component: SecretariatOfficerView },
            {
              path: 'attendance',
              name: 'admin.secretariat.attendance',
              component: AcademicAttendanceOverview,
              meta: { permission: 'attendance.view' },
            },
            {
              path: 'attendance/subjects/:subjectId',
              name: 'admin.secretariat.attendance.subject',
              component: AcademicSubjectAttendanceView,
              meta: { permission: 'attendance.view' },
            },
            {
              path: 'attendance/levels/:levelId',
              name: 'admin.secretariat.attendance.level',
              component: AcademicLevelAttendanceView,
              meta: { permission: 'attendance.view' },
            },
            {
              path: 'attendance/sessions/:sessionId',
              name: 'admin.secretariat.attendance.sheet',
              component: AcademicAttendanceSheetView,
              meta: { permission: 'attendance.view' },
            },
            {
              path: 'timetable',
              name: 'admin.secretariat.timetable',
              component: AcademicTimetableView,
              meta: { permission: 'attendance.view' },
            },
            {
              path: 'teachers',
              name: 'admin.secretariat.teachers',
              component: AcademicTeachersView,
              meta: { permission: 'teacher.view' },
            },
            {
              path: 'class-counselor',
              name: 'admin.secretariat.classCounselor',
              component: AcademicClassStaffView,
              meta: { permission: 'teacher.view', classStaffRole: 'counselor' },
            },
            {
              path: 'class-supervisor',
              name: 'admin.secretariat.classSupervisor',
              component: AcademicClassStaffView,
              meta: { permission: 'teacher.view', classStaffRole: 'supervisor' },
            },
            {
              path: 'class-staff',
              redirect: { name: 'admin.secretariat.classCounselor' },
            },
            {
              path: 'students',
              name: 'admin.secretariat.students',
              component: AcademicStudentsView,
              meta: { permission: 'student.view' },
            },
            {
              path: 'student-file',
              name: 'admin.secretariat.studentFile',
              component: AcademicStudentFileView,
              meta: { permission: 'student.view' },
            },
            {
              path: 'exams',
              name: 'admin.secretariat.exams',
              component: AcademicExamsView,
              meta: { permission: 'exam.view' },
            },
            {
              path: 'exams/:examId',
              name: 'admin.secretariat.exams.sheet',
              component: AcademicExamSheetView,
              meta: { permission: 'exam.view' },
            },
            {
              path: 'achievement',
              name: 'admin.secretariat.achievement',
              component: AcademicAchievementView,
              meta: { permission: 'exam.view' },
            },
            {
              path: 'achievement/students/:studentId',
              name: 'admin.secretariat.achievement.student',
              component: AcademicStudentAchievementView,
              meta: { permission: 'exam.view' },
            },
            {
              path: 'members',
              name: 'admin.secretariat.members',
              component: StatisticsMembersView,
              meta: { permission: 'member.view' },
            },
            {
              path: 'help-requests',
              name: 'admin.secretariat.help',
              component: SocialHelpRequestsView,
              meta: { permission: 'help.view' },
            },
            {
              path: 'visits',
              name: 'admin.secretariat.visits',
              component: SocialVisitsView,
              meta: { permission: 'help.view' },
            },
            {
              path: 'women-members',
              name: 'admin.secretariat.womenMembers',
              component: WomenMembersView,
              meta: { permission: 'women_member.view' },
            },
            {
              path: 'teams',
              name: 'admin.secretariat.sports.teams',
              component: SportsTeamsView,
              meta: { permission: 'sport.view' },
            },
            {
              path: 'teams/:teamId',
              name: 'admin.secretariat.sports.team',
              component: SportsTeamHubView,
              meta: { permission: 'sport.view' },
            },
            {
              path: 'national-team',
              name: 'admin.secretariat.sports.national',
              component: SportsNationalView,
              meta: { permission: 'sport.view' },
            },
            {
              path: 'sports-join-requests',
              name: 'admin.secretariat.sports.join',
              component: SportsJoinRequestsView,
              meta: { permission: 'sport.view' },
            },
            {
              path: 'accounts',
              name: 'admin.secretariat.finance',
              component: FinanceOverviewView,
              meta: { permission: 'finance.view' },
            },
            {
              path: 'revenues',
              name: 'admin.secretariat.finance.revenues',
              component: FinanceRevenuesView,
              meta: { permission: 'finance.view' },
            },
            {
              path: 'expenses',
              name: 'admin.secretariat.finance.expenses',
              component: FinanceExpensesView,
              meta: { permission: 'finance.view' },
            },
            {
              path: 'documents',
              name: 'admin.secretariat.finance.documents',
              component: FinanceDocumentsView,
              meta: { permission: 'finance.view' },
            },
            {
              path: 'decisions',
              name: 'admin.secretariat.decisions',
              component: MediaDecisionsView,
              meta: { permission: 'decision.view' },
            },
            {
              path: 'meeting-outputs',
              name: 'admin.secretariat.meetingOutputs',
              component: SecretariatMeetingOutputsView,
              meta: { permission: 'decision.view' },
            },
            {
              path: 'media-center',
              name: 'admin.secretariat.mediaCenter',
              component: MediaCenterView,
              meta: { permission: 'press.view' },
            },
            {
              path: 'partners',
              name: 'admin.secretariat.partners',
              component: ExternalPartnersView,
              meta: { permission: 'partner.view' },
            },
            {
              path: 'files',
              name: 'admin.secretariat.files',
              component: ExternalDocumentsView,
              meta: { permission: 'partner.view' },
            },
            {
              path: 'contact-requests',
              name: 'admin.secretariat.contactRequests',
              component: ExternalContactRequestsView,
              meta: { permission: 'extcontact.view' },
            },
            {
              path: 'inbox',
              name: 'admin.secretariat.inbox',
              component: SecretariatMessagesView,
              meta: { permission: 'inbox.view' },
            },
            {
              path: 'messages',
              redirect: (to) => `/admin/secretariats/${to.params.code}/inbox`,
            },
            {
              path: 'presidential-directives',
              name: 'admin.secretariat.presidentialDirectives',
              component: PresidentialDirectivesView,
            },
            {
              path: 'directives',
              name: 'admin.secretariat.directives',
              component: SecretariatDirectivesView,
            },
          ],
        },
        {
          path: 'teacher',
          component: TeacherAdminShell,
          meta: { roles: ['TEACHER', 'SUPER_ADMIN'] },
          children: [
            { path: '', name: 'admin.teacher', component: TeacherHomeView },
            {
              path: 'students',
              name: 'admin.teacher.students',
              component: AcademicStudentsView,
              meta: { permission: 'student.view' },
            },
            {
              path: 'attendance',
              name: 'admin.teacher.attendance',
              component: TeacherAttendanceRegisterView,
              meta: { permission: 'attendance.view' },
            },
            {
              path: 'attendance/subjects/:subjectId',
              name: 'admin.teacher.attendance.subject',
              component: AcademicSubjectAttendanceView,
              meta: { permission: 'attendance.view' },
            },
            {
              path: 'attendance/levels/:levelId',
              name: 'admin.teacher.attendance.level',
              component: AcademicLevelAttendanceView,
              meta: { permission: 'attendance.view' },
            },
            {
              path: 'attendance/sessions/:sessionId',
              name: 'admin.teacher.attendance.sheet',
              component: AcademicAttendanceSheetView,
              meta: { permission: 'attendance.view' },
            },
            {
              path: 'timetable',
              name: 'admin.teacher.timetable',
              component: AcademicTimetableView,
              meta: { permission: 'attendance.view' },
            },
            {
              path: 'lesson-prep',
              name: 'admin.teacher.lessonPrep',
              component: TeacherLessonPrepView,
            },
            {
              path: 'exams',
              name: 'admin.teacher.exams',
              component: AcademicExamsView,
              meta: { permission: 'exam.view' },
            },
            {
              path: 'exams/:examId',
              name: 'admin.teacher.exams.sheet',
              component: AcademicExamSheetView,
              meta: { permission: 'exam.view' },
            },
            {
              path: 'achievement',
              name: 'admin.teacher.achievement',
              component: AcademicAchievementView,
              meta: { permission: 'exam.view' },
            },
            {
              path: 'achievement/students/:studentId',
              name: 'admin.teacher.achievement.student',
              component: AcademicStudentAchievementView,
              meta: { permission: 'exam.view' },
            },
          ],
        },
        {
          path: 'shura',
          component: ShuraAdminShell,
          children: [
            { path: '', name: 'admin.shura', component: ShuraOverviewView },
            { path: 'members', name: 'admin.shura.members', component: ShuraMembersView },
            { path: 'meetings', name: 'admin.shura.meetings', component: ShuraMeetingsView },
          ],
        },
        {
          path: 'parents',
          component: ParentsAdminShell,
          children: [
            { path: '', name: 'admin.parents', component: ParentsHomeView },
            { path: 'members', name: 'admin.parents.members', component: ParentsMembersView },
            { path: 'registrations', name: 'admin.parents.registrations', component: ParentsRegistrationsView },
            { path: 'meetings', name: 'admin.parents.meetings', component: ParentsMeetingsView },
            { path: 'surveys', name: 'admin.parents.surveys', component: ParentsSurveysView },
          ],
        },
        { path: 'users', name: 'admin.users', component: UsersView, meta: { permission: 'user.view' } },
        { path: 'users/create', name: 'admin.users.create', component: UserFormView, meta: { permission: 'user.create' } },
        { path: 'users/:id', name: 'admin.users.edit', component: UserFormView, meta: { permission: 'user.update' } },
        { path: 'roles', name: 'admin.roles', component: RolesView, meta: { permission: 'role.view' } },
      ],
    },
  ],
  scrollBehavior(to) {
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0 }
  },
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  await auth.bootstrap()

  if (to.path.startsWith('/admin') && auth.isAuthenticated) {
    rememberAdminPath(to.fullPath)
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return resolveAdminEntryPath(auth.user)
  }

  if (to.meta.permission && !auth.hasPermission(to.meta.permission)) {
    return resolvePostLoginPath(auth.user)
  }

  if (to.meta.anyPermission?.length) {
    const allowed = to.meta.anyPermission.some((permission) => auth.hasPermission(permission))
    if (!allowed) {
      return resolvePostLoginPath(auth.user)
    }
  }

  if (to.meta.roles?.length) {
    const codes = (auth.user?.roles || []).map((role) => role.code)
    const allowed = to.meta.roles.some((role) => codes.includes(role))
    if (!allowed) {
      return resolvePostLoginPath(auth.user)
    }
  }

  return true
})

export default router
