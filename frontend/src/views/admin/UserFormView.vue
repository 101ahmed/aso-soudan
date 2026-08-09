<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { createUser, getUser, listRoles, updateUser } from '@/services/users'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()

const isEdit = computed(() => Boolean(route.params.id))
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const roles = ref([])

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  locale: 'fr',
  status: 'active',
  password: '',
  password_confirmation: '',
  role_ids: [],
})

function roleLabel(role) {
  return locale.value === 'ar' ? role.name_ar : role.name_fr
}

function toggleRole(roleId) {
  const id = Number(roleId)
  if (form.role_ids.includes(id)) {
    form.role_ids = form.role_ids.filter((item) => item !== id)
  } else {
    form.role_ids.push(id)
  }
}

async function load() {
  loading.value = true
  try {
    roles.value = await listRoles()
    if (isEdit.value) {
      const user = await getUser(route.params.id)
      form.first_name = user.first_name || ''
      form.last_name = user.last_name || ''
      form.email = user.email || ''
      form.phone = user.phone || ''
      form.locale = user.locale || 'fr'
      form.status = user.status || 'active'
      form.role_ids = (user.roles || []).map((role) => role.id)
    }
  } catch (err) {
    error.value = err.response?.data?.message || t('admin.users.loadError')
  } finally {
    loading.value = false
  }
}

async function submit() {
  saving.value = true
  error.value = ''
  try {
    const payload = {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      phone: form.phone || null,
      locale: form.locale,
      status: form.status,
      role_ids: form.role_ids,
    }

    if (form.password) {
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
    }

    if (isEdit.value) {
      await updateUser(route.params.id, payload)
    } else {
      if (!form.password) {
        error.value = t('admin.users.passwordRequired')
        return
      }
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
      await createUser(payload)
    }

    router.push('/admin/users')
  } catch (err) {
    const errors = err.response?.data?.errors
    error.value = errors
      ? Object.values(errors).flat().join(' ')
      : err.response?.data?.message || t('admin.users.saveError')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="mx-auto max-w-3xl space-y-5">
    <div>
      <h1 class="text-2xl font-semibold">
        {{ isEdit ? t('admin.users.editTitle') : t('admin.users.createTitle') }}
      </h1>
      <p class="text-sm text-slate-600">{{ t('admin.users.formSubtitle') }}</p>
    </div>

    <p v-if="loading" class="text-sm text-slate-600">{{ t('admin.loading') }}</p>

    <form v-else class="space-y-4 rounded-xl border border-slate-200 bg-white p-6" @submit.prevent="submit">
      <div class="grid gap-4 md:grid-cols-2">
        <label class="text-sm">
          <span class="mb-1 block">{{ t('admin.users.firstName') }}</span>
          <input v-model="form.first_name" required class="w-full rounded-md border border-slate-300 px-3 py-2" />
        </label>
        <label class="text-sm">
          <span class="mb-1 block">{{ t('admin.users.lastName') }}</span>
          <input v-model="form.last_name" required class="w-full rounded-md border border-slate-300 px-3 py-2" />
        </label>
        <label class="text-sm md:col-span-2">
          <span class="mb-1 block">{{ t('admin.users.email') }}</span>
          <input v-model="form.email" type="email" required class="w-full rounded-md border border-slate-300 px-3 py-2" />
        </label>
        <label class="text-sm">
          <span class="mb-1 block">{{ t('admin.users.phone') }}</span>
          <input v-model="form.phone" class="w-full rounded-md border border-slate-300 px-3 py-2" />
        </label>
        <label class="text-sm">
          <span class="mb-1 block">{{ t('admin.users.status') }}</span>
          <select v-model="form.status" class="w-full rounded-md border border-slate-300 px-3 py-2">
            <option value="active">active</option>
            <option value="inactive">inactive</option>
            <option value="suspended">suspended</option>
          </select>
        </label>
        <label class="text-sm">
          <span class="mb-1 block">{{ t('admin.users.password') }}</span>
          <input v-model="form.password" type="password" class="w-full rounded-md border border-slate-300 px-3 py-2" />
        </label>
        <label class="text-sm">
          <span class="mb-1 block">{{ t('admin.users.passwordConfirm') }}</span>
          <input v-model="form.password_confirmation" type="password" class="w-full rounded-md border border-slate-300 px-3 py-2" />
        </label>
      </div>

      <fieldset class="rounded-lg border border-slate-200 p-4">
        <legend class="px-2 text-sm font-medium">{{ t('admin.users.assignRoles') }}</legend>
        <div class="grid gap-2 sm:grid-cols-2">
          <label
            v-for="role in roles"
            :key="role.id"
            class="flex items-center gap-2 rounded-md border border-slate-100 px-3 py-2 text-sm"
          >
            <input
              type="checkbox"
              :checked="form.role_ids.includes(role.id)"
              @change="toggleRole(role.id)"
            />
            <span>
              <span class="font-medium">{{ role.code }}</span>
              <span class="text-slate-500"> — {{ roleLabel(role) }}</span>
            </span>
          </label>
        </div>
      </fieldset>

      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>

      <div class="flex gap-3">
        <button
          type="submit"
          class="rounded-md bg-teal-800 px-4 py-2 text-sm text-white hover:bg-teal-700 disabled:opacity-60"
          :disabled="saving"
        >
          {{ saving ? t('admin.saving') : t('admin.save') }}
        </button>
        <button
          type="button"
          class="rounded-md border border-slate-300 px-4 py-2 text-sm"
          @click="router.push('/admin/users')"
        >
          {{ t('admin.cancel') }}
        </button>
      </div>
    </form>
  </section>
</template>
