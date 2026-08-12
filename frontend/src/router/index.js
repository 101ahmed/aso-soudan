import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { resolvePostLoginPath } from '@/utils/roleRedirect'
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
import EventsListView from '@/views/public/EventsListView.vue'
import EventDetailView from '@/views/public/EventDetailView.vue'
import GalleryView from '@/views/public/GalleryView.vue'
import GalleryAlbumView from '@/views/public/GalleryAlbumView.vue'
import ContactView from '@/views/public/ContactView.vue'
import StudentRegisterView from '@/views/public/StudentRegisterView.vue'
import MemberRegisterView from '@/views/public/MemberRegisterView.vue'
import LoginView from '@/views/auth/LoginView.vue'
import ForgotPasswordView from '@/views/auth/ForgotPasswordView.vue'
import ResetPasswordView from '@/views/auth/ResetPasswordView.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import DashboardView from '@/views/admin/DashboardView.vue'
import PresidentDashboardView from '@/views/admin/PresidentDashboardView.vue'
import SecretariatAdminShell from '@/views/admin/secretariat/SecretariatAdminShell.vue'
import SecretariatHomeView from '@/views/admin/secretariat/SecretariatHomeView.vue'
import SecretariatNewsView from '@/views/admin/secretariat/SecretariatNewsView.vue'
import SecretariatAnnouncementsView from '@/views/admin/secretariat/SecretariatAnnouncementsView.vue'
import SecretariatAlbumsView from '@/views/admin/secretariat/SecretariatAlbumsView.vue'
import SecretariatOfficerView from '@/views/admin/secretariat/SecretariatOfficerView.vue'
import AcademicAttendanceOverview from '@/views/admin/academic/AcademicAttendanceOverview.vue'
import AcademicSubjectAttendanceView from '@/views/admin/academic/AcademicSubjectAttendanceView.vue'
import AcademicAttendanceSheetView from '@/views/admin/academic/AcademicAttendanceSheetView.vue'
import ShuraAdminShell from '@/views/admin/shura/ShuraAdminShell.vue'
import ShuraOverviewView from '@/views/admin/shura/ShuraOverviewView.vue'
import ShuraMembersView from '@/views/admin/shura/ShuraMembersView.vue'
import ShuraMeetingsView from '@/views/admin/shura/ShuraMeetingsView.vue'
import UsersView from '@/views/admin/UsersView.vue'
import UserFormView from '@/views/admin/UserFormView.vue'
import RolesView from '@/views/admin/RolesView.vue'

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
        { path: 'events', name: 'events', component: EventsListView },
        { path: 'events/:slug', name: 'events.detail', component: EventDetailView },
        { path: 'gallery', name: 'gallery', component: GalleryView },
        { path: 'gallery/:slug', name: 'gallery.album', component: GalleryAlbumView },
        { path: 'contact', name: 'contact', component: ContactView },
        { path: 'register/student', name: 'register.student', component: StudentRegisterView },
        { path: 'register/member', name: 'register.member', component: MemberRegisterView },
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
          path: 'president',
          name: 'admin.president',
          component: PresidentDashboardView,
          meta: { roles: ['PRESIDENT', 'SUPER_ADMIN'] },
        },
        {
          path: 'secretariats/:code',
          component: SecretariatAdminShell,
          props: true,
          children: [
            { path: '', name: 'admin.secretariat', component: SecretariatHomeView },
            { path: 'news', name: 'admin.secretariat.news', component: SecretariatNewsView },
            { path: 'announcements', name: 'admin.secretariat.announcements', component: SecretariatAnnouncementsView },
            { path: 'albums', name: 'admin.secretariat.albums', component: SecretariatAlbumsView },
            { path: 'officer', name: 'admin.secretariat.officer', component: SecretariatOfficerView },
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
              path: 'attendance/sessions/:sessionId',
              name: 'admin.secretariat.attendance.sheet',
              component: AcademicAttendanceSheetView,
              meta: { permission: 'attendance.view' },
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

  if (auth.token && !auth.user) {
    try {
      await auth.fetchMe()
    } catch {
      auth.clear()
    }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return resolvePostLoginPath(auth.user)
  }

  if (to.meta.permission && !auth.hasPermission(to.meta.permission)) {
    return resolvePostLoginPath(auth.user)
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
