<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { fetchMyDepartments } from '@/services/content'
import { canAccessDepartment, isSecretariatCode, SECRETARIAT_NAME_KEYS } from '@/utils/departmentAccess'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const auth = useAuthStore()
const departments = ref([])

const canSeeInbox = computed(() => auth.hasPermission('inbox.view'))
const canManageContent = computed(
  () => auth.hasPermission('news.view') || auth.hasPermission('announcement.view'),
)

const secretariats = computed(() =>
  (departments.value || [])
    .filter((dept) => isSecretariatCode(dept.code) && canAccessDepartment(auth.user, dept.code))
    .map((dept) => ({
      ...dept,
      label: pickName(dept, locale.value) || t(SECRETARIAT_NAME_KEYS[dept.code] || dept.code),
    })),
)

onMounted(async () => {
  if (!canSeeInbox.value) return
  try {
    departments.value = await fetchMyDepartments()
  } catch {
    departments.value = []
  }
})
</script>

<template>
  <section class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold">{{ t('admin.dashboard.title') }}</h1>
      <p class="text-slate-600">{{ t('admin.dashboard.welcome', { name: auth.fullName }) }}</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-500">{{ t('admin.dashboard.roles') }}</p>
        <p class="mt-2 text-lg font-medium">
          {{ auth.user?.roles?.map((r) => r.code).join(', ') || '—' }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 md:col-span-2">
        <p class="text-sm text-slate-500">{{ t('admin.dashboard.permissions') }}</p>
        <p class="mt-2 text-sm text-slate-700">
          {{ (auth.user?.permissions || []).slice(0, 8).join(' · ') }}
          <span v-if="(auth.user?.permissions || []).length > 8">…</span>
        </p>
      </div>
    </div>

    <div v-if="auth.hasPermission('report.view')" class="rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="font-semibold text-[var(--rdp-forest)]">{{ t('secretariatReports.hubTitle') }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t('secretariatReports.hubHint') }}</p>
      <RouterLink
        to="/admin/reports"
        class="mt-3 inline-flex rounded bg-teal-800 px-3 py-1.5 text-sm font-semibold text-white"
      >
        {{ t('secretariatReports.open') }}
      </RouterLink>
    </div>

    <div v-if="canManageContent" class="space-y-3">
      <div>
        <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('admin.dashboard.contentTitle') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ t('admin.dashboard.contentHint') }}</p>
      </div>
      <div class="grid gap-3 sm:grid-cols-2">
        <RouterLink
          v-if="auth.hasPermission('news.view')"
          to="/admin/content/news"
          class="rounded-xl border border-slate-200 bg-white p-4 hover:border-teal-700/40"
        >
          <p class="font-semibold text-[var(--rdp-forest)]">{{ t('contentAdmin.news') }}</p>
          <p class="mt-1 text-sm text-slate-600">{{ t('contentAdmin.newsHint') }}</p>
        </RouterLink>
        <RouterLink
          v-if="auth.hasPermission('announcement.view')"
          to="/admin/content/announcements"
          class="rounded-xl border border-slate-200 bg-white p-4 hover:border-teal-700/40"
        >
          <p class="font-semibold text-[var(--rdp-forest)]">{{ t('contentAdmin.announcements') }}</p>
          <p class="mt-1 text-sm text-slate-600">{{ t('contentAdmin.announcementsHint') }}</p>
        </RouterLink>
      </div>
    </div>

    <div v-if="canSeeInbox && secretariats.length" class="space-y-3">
      <div>
        <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('admin.dashboard.inboxesTitle') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ t('admin.dashboard.inboxesHint') }}</p>
      </div>
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <article
          v-for="dept in secretariats"
          :key="dept.code"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <p class="font-semibold text-[var(--rdp-forest)]">{{ dept.label }}</p>
          <p v-if="dept.unread_messages_count" class="mt-1 text-xs font-medium text-amber-800">
            {{ t('admin.dashboard.unread', { count: dept.unread_messages_count }) }}
          </p>
          <p v-else class="mt-1 text-xs text-slate-500">{{ t('admin.dashboard.noUnread') }}</p>
          <div class="mt-3 flex flex-wrap gap-2">
            <RouterLink
              :to="`/admin/secretariats/${dept.code}/inbox`"
              class="rounded bg-teal-800 px-3 py-1.5 text-xs font-semibold text-white"
            >
              {{ t('secretariatAdmin.messages') }}
            </RouterLink>
            <RouterLink
              :to="`/admin/secretariats/${dept.code}`"
              class="rounded border border-slate-300 px-3 py-1.5 text-xs text-slate-700"
            >
              {{ t('secretariatAdmin.home') }}
            </RouterLink>
            <RouterLink
              v-if="auth.hasPermission('report.view')"
              :to="`/admin/secretariats/${dept.code}/reports`"
              class="rounded border border-slate-300 px-3 py-1.5 text-xs text-slate-700"
            >
              {{ t('secretariatAdmin.reports') }}
            </RouterLink>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
