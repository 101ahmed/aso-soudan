<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()

const cards = computed(() => [
  {
    to: '/admin/teacher/students',
    label: t('teacherAdmin.students'),
    hint: t('teacherAdmin.studentsHint'),
    show: auth.hasPermission('student.view') || auth.user?.roles?.some((r) => r.code === 'TEACHER'),
  },
  {
    to: '/admin/teacher/attendance',
    label: t('teacherAdmin.attendance'),
    hint: t('teacherAdmin.attendanceHint'),
    show: auth.hasPermission('attendance.view'),
  },
].filter((card) => card.show))
</script>

<template>
  <div class="space-y-4">
    <p class="text-sm text-slate-600">{{ t('teacherAdmin.homeIntro') }}</p>
    <div class="grid gap-4 md:grid-cols-2">
      <RouterLink
        v-for="card in cards"
        :key="card.to"
        :to="card.to"
        class="rounded-xl border border-slate-200 bg-white p-5 hover:border-teal-700/40"
      >
        <h2 class="font-semibold text-[var(--rdp-forest)]">{{ card.label }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ card.hint }}</p>
      </RouterLink>
    </div>
  </div>
</template>
