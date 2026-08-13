<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { canAccessDepartment } from '@/utils/departmentAccess'
import { pickName } from '@/utils/localized'

const props = defineProps({
  code: { type: String, required: true },
})

const { t, locale } = useI18n()
const auth = useAuthStore()
const route = useRoute()

const allowed = computed(() => canAccessDepartment(auth.user, props.code, { write: false }))
const base = computed(() => `/admin/secretariats/${props.code}`)

const links = computed(() => {
  const items = [
    { to: base.value, label: t('secretariatAdmin.home'), exact: true },
    { to: `${base.value}/officer`, label: t('secretariatAdmin.officer') },
    { to: `${base.value}/news`, label: t('secretariatAdmin.news') },
    { to: `${base.value}/announcements`, label: t('secretariatAdmin.announcements') },
    { to: `${base.value}/albums`, label: t('secretariatAdmin.albums') },
  ]
  if (props.code === 'academic' && (auth.hasPermission('attendance.view') || auth.hasPermission('student.view'))) {
    items.push({ to: `${base.value}/attendance`, label: t('secretariatAdmin.attendance') })
  }
  if (props.code === 'academic' && auth.hasPermission('teacher.view')) {
    items.push({ to: `${base.value}/teachers`, label: t('secretariatAdmin.teachers') })
  }
  if (props.code === 'academic' && auth.hasPermission('student.view')) {
    items.push({ to: `${base.value}/students`, label: t('secretariatAdmin.students') })
  }
  return items
})

function isActive(link) {
  if (link.exact) return route.path === link.to
  return route.path.startsWith(link.to)
}

const title = computed(() => {
  const dept = (auth.user?.departments || []).find((d) => d.code === props.code)
  if (!dept) return props.code
  return pickName(dept, locale.value)
})
</script>

<template>
  <section v-if="!allowed" class="rounded-xl border border-rose-200 bg-rose-50 p-8 text-center">
    <p class="text-lg font-semibold text-rose-800">403</p>
    <p class="mt-2 text-sm text-rose-700">{{ t('secretariatAdmin.forbidden') }}</p>
    <RouterLink to="/admin" class="mt-4 inline-flex text-sm text-[var(--rdp-forest)] hover:underline">
      {{ t('admin.nav.dashboard') }}
    </RouterLink>
  </section>

  <section v-else class="space-y-6">
    <div>
      <p class="text-xs tracking-wide text-slate-500 uppercase">{{ t('secretariatAdmin.badge') }}</p>
      <h1 class="mt-1 text-2xl font-semibold text-[var(--rdp-forest)]">{{ title }}</h1>
    </div>

    <nav class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
      <RouterLink
        v-for="link in links"
        :key="link.to"
        :to="link.to"
        class="rounded-md px-3 py-1.5 text-sm"
        :class="isActive(link) ? 'bg-teal-800 text-white' : 'bg-white text-slate-700 hover:bg-slate-100'"
      >
        {{ link.label }}
      </RouterLink>
    </nav>

    <RouterView />
  </section>
</template>
