<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { disableUser, listRoles, listUsers } from '@/services/users'

const { t, locale } = useI18n()
const auth = useAuthStore()

const loading = ref(false)
const error = ref('')
const users = ref([])
const meta = ref(null)
const roles = ref([])

const filters = reactive({
  search: '',
  status: '',
  role_id: '',
  page: 1,
})

const canCreate = computed(() => auth.hasPermission('user.create'))
const canUpdate = computed(() => auth.hasPermission('user.update'))

function roleLabel(role) {
  return locale.value === 'ar' ? role.name_ar : role.name_fr
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const params = {
      page: filters.page,
      search: filters.search || undefined,
      status: filters.status || undefined,
      role_id: filters.role_id || undefined,
    }
    const response = await listUsers(params)
    users.value = response.data || []
    meta.value = response.meta || null
  } catch (err) {
    error.value = err.response?.data?.message || t('admin.users.loadError')
  } finally {
    loading.value = false
  }
}

async function onDisable(user) {
  if (!canUpdate.value) return
  if (!confirm(t('admin.users.confirmDisable', { name: user.name }))) return
  await disableUser(user.id)
  await load()
}

onMounted(async () => {
  roles.value = await listRoles()
  await load()
})
</script>

<template>
  <section class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold">{{ t('admin.users.title') }}</h1>
        <p class="text-sm text-slate-600">{{ t('admin.users.subtitle') }}</p>
      </div>
      <RouterLink
        v-if="canCreate"
        to="/admin/users/create"
        class="rounded-md bg-teal-800 px-4 py-2 text-sm text-white hover:bg-teal-700"
      >
        {{ t('admin.users.create') }}
      </RouterLink>
    </div>

    <div class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
      <input
        v-model="filters.search"
        type="search"
        :placeholder="t('admin.users.search')"
        class="rounded-md border border-slate-300 px-3 py-2 text-sm"
        @keyup.enter="filters.page = 1; load()"
      />
      <select v-model="filters.status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <option value="">{{ t('admin.users.allStatuses') }}</option>
        <option value="active">active</option>
        <option value="inactive">inactive</option>
        <option value="suspended">suspended</option>
      </select>
      <select v-model="filters.role_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <option value="">{{ t('admin.users.allRoles') }}</option>
        <option v-for="role in roles" :key="role.id" :value="role.id">
          {{ roleLabel(role) }}
        </option>
      </select>
      <button
        type="button"
        class="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50"
        @click="filters.page = 1; load()"
      >
        {{ t('admin.users.filter') }}
      </button>
    </div>

    <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
    <p v-if="loading" class="text-sm text-slate-600">{{ t('admin.loading') }}</p>

    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-start">
          <tr>
            <th class="px-4 py-3 font-medium">{{ t('admin.users.name') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.users.email') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.users.status') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.users.roles') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.users.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id" class="border-t border-slate-100">
            <td class="px-4 py-3">{{ user.name }}</td>
            <td class="px-4 py-3">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-0.5 text-xs"
                :class="user.status === 'active' ? 'bg-teal-100 text-teal-900' : 'bg-slate-200 text-slate-700'"
              >
                {{ user.status }}
              </span>
            </td>
            <td class="px-4 py-3">
              {{ (user.roles || []).map((r) => r.code).join(', ') || '—' }}
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <RouterLink
                  v-if="canUpdate"
                  :to="`/admin/users/${user.id}`"
                  class="text-teal-800 hover:underline"
                >
                  {{ t('admin.users.edit') }}
                </RouterLink>
                <button
                  v-if="canUpdate && user.status === 'active'"
                  type="button"
                  class="text-rose-700 hover:underline"
                  @click="onDisable(user)"
                >
                  {{ t('admin.users.disable') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="meta" class="flex items-center gap-3 text-sm">
      <button
        type="button"
        class="rounded border px-3 py-1 disabled:opacity-40"
        :disabled="meta.current_page <= 1"
        @click="filters.page -= 1; load()"
      >
        {{ t('admin.prev') }}
      </button>
      <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
      <button
        type="button"
        class="rounded border px-3 py-1 disabled:opacity-40"
        :disabled="meta.current_page >= meta.last_page"
        @click="filters.page += 1; load()"
      >
        {{ t('admin.next') }}
      </button>
    </div>
  </section>
</template>
