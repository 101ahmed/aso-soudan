<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { deleteParentRegistration, fetchParentRegistrations, updateParentRegistration } from '@/services/parents'

const { t } = useI18n()
const auth = useAuthStore()
const items = ref([])
const error = ref('')
const loading = ref(false)
const canManage = () => auth.hasPermission('parents.registration.manage')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchParentRegistrations({ per_page: 50 })
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function setStatus(item, status) {
  try {
    await updateParentRegistration(item.id, { status, notes: item.notes || null })
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function remove(item) {
  if (!confirm(t('parentsAdmin.confirmDeleteParent', { name: item.full_name }))) return
  try {
    await deleteParentRegistration(item.id)
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-4">
    <h2 class="text-lg font-semibold">{{ t('parentsAdmin.registrations') }}</h2>
    <p class="text-sm text-slate-600">{{ t('parentsAdmin.registrationsHint') }}</p>
    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
    <p v-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('parentsAdmin.emptyRegistrations') }}</p>
    <article v-for="item in items" :key="item.id" class="rounded-xl border bg-white p-5">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p class="font-semibold">{{ item.full_name }}</p>
          <p class="mt-1 text-sm text-slate-600">{{ item.phone }} <span v-if="item.email">· {{ item.email }}</span></p>
          <p v-if="item.city" class="text-sm text-slate-500">{{ item.city }}</p>
        </div>
        <select
          v-if="canManage()"
          class="rounded border px-2 py-1 text-sm"
          :value="item.status"
          @change="setStatus(item, $event.target.value)"
        >
          <option value="pending">{{ t('parentsAdmin.statuses.pending') }}</option>
          <option value="active">{{ t('parentsAdmin.statuses.active') }}</option>
          <option value="archived">{{ t('parentsAdmin.statuses.archived') }}</option>
        </select>
        <p v-else class="text-sm text-slate-500">{{ t(`parentsAdmin.statuses.${item.status}`) }}</p>
      </div>
      <ul class="mt-3 space-y-1 text-sm text-slate-700">
        <li v-for="child in item.children || []" :key="child.id">
          {{ child.first_name }} {{ child.last_name }}
          <span v-if="child.level" class="text-slate-500">· {{ child.level }}</span>
          <span v-if="child.birth_date" class="text-slate-500">· {{ child.birth_date }}</span>
        </li>
      </ul>
      <button
        v-if="canManage()"
        type="button"
        class="mt-3 text-sm text-rose-700 hover:underline"
        @click="remove(item)"
      >
        {{ t('forms.delete') }}
      </button>
    </article>
  </div>
</template>
