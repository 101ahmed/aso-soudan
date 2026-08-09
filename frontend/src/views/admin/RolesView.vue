<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { listPermissions, listRoles, syncRolePermissions } from '@/services/users'

const { t, locale } = useI18n()
const auth = useAuthStore()

const roles = ref([])
const permissions = ref([])
const selectedRoleId = ref(null)
const selectedPermissionIds = ref([])
const loading = ref(false)
const saving = ref(false)
const message = ref('')
const error = ref('')

const canAssign = () => auth.hasPermission('permission.assign')

function labelRole(role) {
  return locale.value === 'ar' ? role.name_ar : role.name_fr
}

function labelPermission(permission) {
  return locale.value === 'ar' ? permission.name_ar : permission.name_fr
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    roles.value = await listRoles({ with_permissions: 1 })
    permissions.value = await listPermissions()
    if (roles.value.length) {
      selectRole(roles.value[0])
    }
  } catch (err) {
    error.value = err.response?.data?.message || t('admin.roles.loadError')
  } finally {
    loading.value = false
  }
}

function selectRole(role) {
  selectedRoleId.value = role.id
  selectedPermissionIds.value = (role.permissions || []).map((p) => p.id)
  message.value = ''
}

function togglePermission(permissionId) {
  const id = Number(permissionId)
  if (selectedPermissionIds.value.includes(id)) {
    selectedPermissionIds.value = selectedPermissionIds.value.filter((item) => item !== id)
  } else {
    selectedPermissionIds.value.push(id)
  }
}

async function save() {
  if (!canAssign() || !selectedRoleId.value) return
  saving.value = true
  message.value = ''
  error.value = ''
  try {
    const updated = await syncRolePermissions(selectedRoleId.value, selectedPermissionIds.value)
    const index = roles.value.findIndex((role) => role.id === updated.id)
    if (index >= 0) roles.value[index] = updated
    message.value = t('admin.roles.saved')
  } catch (err) {
    error.value = err.response?.data?.message || t('admin.roles.saveError')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="space-y-5">
    <div>
      <h1 class="text-2xl font-semibold">{{ t('admin.roles.title') }}</h1>
      <p class="text-sm text-slate-600">{{ t('admin.roles.subtitle') }}</p>
    </div>

    <p v-if="loading" class="text-sm text-slate-600">{{ t('admin.loading') }}</p>
    <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
    <p v-if="message" class="text-sm text-teal-800">{{ message }}</p>

    <div v-if="!loading" class="grid gap-4 lg:grid-cols-[280px_1fr]">
      <div class="rounded-xl border border-slate-200 bg-white p-3">
        <button
          v-for="role in roles"
          :key="role.id"
          type="button"
          class="mb-1 w-full rounded-md px-3 py-2 text-start text-sm"
          :class="selectedRoleId === role.id ? 'bg-teal-800 text-white' : 'hover:bg-slate-50'"
          @click="selectRole(role)"
        >
          <span class="font-medium">{{ role.code }}</span>
          <span class="block text-xs opacity-80">{{ labelRole(role) }}</span>
        </button>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h2 class="font-medium">{{ t('admin.roles.assignPermissions') }}</h2>
          <button
            v-if="canAssign()"
            type="button"
            class="rounded-md bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-60"
            :disabled="saving || !selectedRoleId"
            @click="save"
          >
            {{ saving ? t('admin.saving') : t('admin.save') }}
          </button>
        </div>

        <div class="grid gap-2 sm:grid-cols-2">
          <label
            v-for="permission in permissions"
            :key="permission.id"
            class="flex items-start gap-2 rounded-md border border-slate-100 px-3 py-2 text-sm"
          >
            <input
              type="checkbox"
              class="mt-1"
              :disabled="!canAssign()"
              :checked="selectedPermissionIds.includes(permission.id)"
              @change="togglePermission(permission.id)"
            />
            <span>
              <span class="font-medium">{{ permission.code }}</span>
              <span class="block text-xs text-slate-500">{{ labelPermission(permission) }}</span>
            </span>
          </label>
        </div>
      </div>
    </div>
  </section>
</template>
