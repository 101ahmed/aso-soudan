<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createMember,
  deleteMember,
  fetchMembers,
  sendMemberMessage,
  updateMember,
} from '@/services/members'

const { t } = useI18n()
const auth = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const sending = ref(false)
const error = ref('')
const notice = ref('')
const items = ref([])
const cities = ref([])
const meta = ref(null)
const emailCount = ref(0)
const editingId = ref(null)
const selected = ref([])
const showMessage = ref(false)
const messageMode = ref('selected')

const filters = reactive({
  search: '',
  status: '',
  gender: '',
  city: '',
  membership_type: '',
  age_min: '',
  age_max: '',
  page: 1,
})

const form = reactive(emptyForm())
const message = reactive({ subject: '', body: '' })

const canView = computed(() => auth.hasPermission('member.view'))
const canCreate = computed(() => auth.hasPermission('member.create'))
const canUpdate = computed(() => auth.hasPermission('member.update'))
const canDelete = computed(() => auth.hasPermission('member.delete'))
const canMessage = computed(() => auth.hasPermission('member.message'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))
const allOnPageSelected = computed(() => items.value.length > 0 && items.value.every((item) => selected.value.includes(item.id)))
const selectedCount = computed(() => selected.value.length)

const membershipTypes = ['adherent', 'volunteer', 'supporter', 'student', 'family', 'other']

function emptyForm() {
  return {
    first_name: '',
    last_name: '',
    birth_date: '',
    gender: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    membership_type: '',
    status: 'active',
    notes: '',
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
}

function filterParams() {
  return {
    search: filters.search || undefined,
    status: filters.status || undefined,
    gender: filters.gender || undefined,
    city: filters.city || undefined,
    membership_type: filters.membership_type || undefined,
    age_min: filters.age_min || undefined,
    age_max: filters.age_max || undefined,
  }
}

async function load() {
  if (!canView.value) return
  loading.value = true
  error.value = ''
  try {
    const response = await fetchMembers({ page: filters.page, ...filterParams() })
    items.value = response.data || []
    meta.value = response.meta || null
    cities.value = response.cities || []
    emailCount.value = response.email_count || 0
    const visibleIds = new Set(items.value.map((item) => item.id))
    selected.value = selected.value.filter((id) => visibleIds.has(id))
  } catch (e) {
    error.value = e.response?.data?.message || e.userMessage || e.message
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  filters.page = 1
  load()
}

function edit(item) {
  editingId.value = item.id
  Object.assign(form, {
    first_name: item.first_name || '',
    last_name: item.last_name || '',
    birth_date: item.birth_date || '',
    gender: item.gender || '',
    email: item.email || '',
    phone: item.phone || '',
    address: item.address || '',
    city: item.city || '',
    membership_type: item.membership_type || '',
    status: item.status || 'active',
    notes: item.notes || '',
  })
}

function payload() {
  return {
    first_name: form.first_name,
    last_name: form.last_name,
    birth_date: form.birth_date || null,
    gender: form.gender || null,
    email: form.email || null,
    phone: form.phone || null,
    address: form.address || null,
    city: form.city || null,
    membership_type: form.membership_type || null,
    status: form.status,
    notes: form.notes || null,
  }
}

async function save() {
  if (!canManage.value) return
  saving.value = true
  error.value = ''
  notice.value = ''
  try {
    if (editingId.value) await updateMember(editingId.value, payload())
    else await createMember(payload())
    resetForm()
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  if (!canDelete.value) return
  if (!confirm(t('statisticsMembers.confirmDelete', { name: item.full_name }))) return
  try {
    await deleteMember(item.id)
    if (editingId.value === item.id) resetForm()
    selected.value = selected.value.filter((id) => id !== item.id)
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

function toggleRow(id) {
  if (selected.value.includes(id)) selected.value = selected.value.filter((item) => item !== id)
  else selected.value = [...selected.value, id]
}

function togglePage() {
  if (allOnPageSelected.value) {
    const ids = new Set(items.value.map((item) => item.id))
    selected.value = selected.value.filter((id) => !ids.has(id))
    return
  }
  const merged = new Set(selected.value)
  items.value.forEach((item) => merged.add(item.id))
  selected.value = [...merged]
}

function openMessage(mode) {
  messageMode.value = mode
  showMessage.value = true
  notice.value = ''
}

async function send() {
  if (!canMessage.value) return
  if (messageMode.value === 'selected' && !selected.value.length) {
    error.value = t('statisticsMembers.selectFirst')
    return
  }
  sending.value = true
  error.value = ''
  notice.value = ''
  try {
    const result = await sendMemberMessage({
      subject: message.subject,
      body: message.body,
      apply_filters: messageMode.value === 'filtered',
      member_ids: messageMode.value === 'selected' ? selected.value : [],
      ...filterParams(),
    })
    notice.value = t('statisticsMembers.sentResult', {
      sent: result.sent,
      skipped: result.skipped,
      failed: result.failed,
    })
    showMessage.value = false
    message.subject = ''
    message.body = ''
    if (messageMode.value === 'selected') selected.value = []
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    sending.value = false
  }
}

watch(() => filters.page, load)
onMounted(load)
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('statisticsMembers.title') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ t('statisticsMembers.subtitle') }}</p>
      </div>
      <div v-if="canMessage" class="flex flex-wrap gap-2">
        <button type="button" class="rounded-md border px-3 py-2 text-sm" :disabled="!selectedCount" @click="openMessage('selected')">
          {{ t('statisticsMembers.messageSelected', { n: selectedCount }) }}
        </button>
        <button type="button" class="rounded-md bg-teal-800 px-3 py-2 text-sm text-white" @click="openMessage('filtered')">
          {{ t('statisticsMembers.messageFiltered', { n: emailCount }) }}
        </button>
      </div>
    </div>

    <p v-if="!canView" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ t('statisticsMembers.forbidden') }}</p>

    <template v-else>
      <div class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-4">
        <input v-model="filters.search" type="search" :placeholder="t('statisticsMembers.search')" class="rounded-md border px-3 py-2 text-sm md:col-span-2" @keyup.enter="applyFilters" />
        <select v-model="filters.status" class="rounded-md border px-3 py-2 text-sm">
          <option value="">{{ t('statisticsMembers.allStatuses') }}</option>
          <option value="pending">{{ t('statisticsMembers.statuses.pending') }}</option>
          <option value="active">{{ t('statisticsMembers.statuses.active') }}</option>
          <option value="inactive">{{ t('statisticsMembers.statuses.inactive') }}</option>
          <option value="archived">{{ t('statisticsMembers.statuses.archived') }}</option>
        </select>
        <select v-model="filters.gender" class="rounded-md border px-3 py-2 text-sm">
          <option value="">{{ t('statisticsMembers.allGenders') }}</option>
          <option value="male">{{ t('statisticsMembers.genders.male') }}</option>
          <option value="female">{{ t('statisticsMembers.genders.female') }}</option>
        </select>
        <select v-model="filters.membership_type" class="rounded-md border px-3 py-2 text-sm">
          <option value="">{{ t('statisticsMembers.allTypes') }}</option>
          <option v-for="type in membershipTypes" :key="type" :value="type">{{ t(`statisticsMembers.types.${type}`) }}</option>
        </select>
        <select v-model="filters.city" class="rounded-md border px-3 py-2 text-sm">
          <option value="">{{ t('statisticsMembers.allCities') }}</option>
          <option v-for="city in cities" :key="city" :value="city">{{ city }}</option>
        </select>
        <input v-model="filters.age_min" type="number" min="0" max="120" :placeholder="t('statisticsMembers.ageMin')" class="rounded-md border px-3 py-2 text-sm" />
        <input v-model="filters.age_max" type="number" min="0" max="120" :placeholder="t('statisticsMembers.ageMax')" class="rounded-md border px-3 py-2 text-sm" />
        <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="applyFilters">{{ t('statisticsMembers.filter') }}</button>
      </div>

      <p v-if="notice" class="rounded border border-teal-200 bg-teal-50 px-3 py-2 text-sm text-teal-900">{{ notice }}</p>
      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <p v-if="loading" class="text-sm text-slate-600">{{ t('admin.loading') }}</p>

      <div class="grid gap-6 lg:grid-cols-[1.35fr_0.65fr]">
        <div class="space-y-3">
          <div class="overflow-x-auto rounded-xl border bg-white">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 text-start text-xs text-slate-500">
                <tr>
                  <th class="px-3 py-3">
                    <input type="checkbox" :checked="allOnPageSelected" @change="togglePage" />
                  </th>
                  <th class="px-3 py-3 font-medium">{{ t('statisticsMembers.name') }}</th>
                  <th class="px-3 py-3 font-medium">{{ t('statisticsMembers.age') }}</th>
                  <th class="px-3 py-3 font-medium">{{ t('statisticsMembers.gender') }}</th>
                  <th class="px-3 py-3 font-medium">{{ t('statisticsMembers.city') }}</th>
                  <th class="px-3 py-3 font-medium">{{ t('statisticsMembers.type') }}</th>
                  <th class="px-3 py-3 font-medium">{{ t('statisticsMembers.status') }}</th>
                  <th class="px-3 py-3"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!loading && !items.length">
                  <td colspan="8" class="px-4 py-6 text-center text-slate-500">{{ t('statisticsMembers.empty') }}</td>
                </tr>
                <tr v-for="item in items" :key="item.id" class="border-t">
                  <td class="px-3 py-3">
                    <input type="checkbox" :checked="selected.includes(item.id)" @change="toggleRow(item.id)" />
                  </td>
                  <td class="px-3 py-3">
                    <p class="font-medium">{{ item.full_name }}</p>
                    <p class="text-xs text-slate-500">{{ item.email || item.phone || '—' }}</p>
                  </td>
                  <td class="px-3 py-3">{{ item.age ?? '—' }}</td>
                  <td class="px-3 py-3">{{ item.gender ? t(`statisticsMembers.genders.${item.gender}`) : '—' }}</td>
                  <td class="px-3 py-3">{{ item.city || '—' }}</td>
                  <td class="px-3 py-3">{{ item.membership_type ? t(`statisticsMembers.types.${item.membership_type}`) : '—' }}</td>
                  <td class="px-3 py-3">{{ t(`statisticsMembers.statuses.${item.status}`) }}</td>
                  <td class="px-3 py-3 whitespace-nowrap">
                    <button v-if="canUpdate" type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                    <button v-if="canDelete" type="button" class="ms-2 text-rose-700 hover:underline" @click="remove(item)">{{ t('forms.delete') }}</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="meta" class="flex items-center gap-3 text-sm">
            <button type="button" class="rounded border px-3 py-1 disabled:opacity-40" :disabled="meta.current_page <= 1" @click="filters.page -= 1">{{ t('admin.prev') }}</button>
            <span>{{ meta.current_page }} / {{ meta.last_page }} · {{ meta.total }}</span>
            <button type="button" class="rounded border px-3 py-1 disabled:opacity-40" :disabled="meta.current_page >= meta.last_page" @click="filters.page += 1">{{ t('admin.next') }}</button>
          </div>
        </div>

        <form v-if="canCreate || canUpdate" class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
          <h3 class="font-semibold">{{ editingId ? t('statisticsMembers.editMember') : t('statisticsMembers.newMember') }}</h3>
          <div class="grid grid-cols-2 gap-2">
            <input v-model="form.first_name" required class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.firstName')" />
            <input v-model="form.last_name" required class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.lastName')" />
          </div>
          <input v-model="form.birth_date" type="date" class="w-full rounded border px-3 py-2 text-sm" />
          <select v-model="form.gender" class="w-full rounded border px-3 py-2 text-sm">
            <option value="">{{ t('statisticsMembers.gender') }}</option>
            <option value="male">{{ t('statisticsMembers.genders.male') }}</option>
            <option value="female">{{ t('statisticsMembers.genders.female') }}</option>
          </select>
          <input v-model="form.email" type="email" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('forms.email')" />
          <input v-model="form.phone" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('forms.phone')" />
          <input v-model="form.city" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('forms.city')" />
          <input v-model="form.address" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('statisticsMembers.address')" />
          <select v-model="form.membership_type" class="w-full rounded border px-3 py-2 text-sm">
            <option value="">{{ t('statisticsMembers.type') }}</option>
            <option v-for="type in membershipTypes" :key="type" :value="type">{{ t(`statisticsMembers.types.${type}`) }}</option>
          </select>
          <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
            <option value="pending">{{ t('statisticsMembers.statuses.pending') }}</option>
            <option value="active">{{ t('statisticsMembers.statuses.active') }}</option>
            <option value="inactive">{{ t('statisticsMembers.statuses.inactive') }}</option>
            <option value="archived">{{ t('statisticsMembers.statuses.archived') }}</option>
          </select>
          <textarea v-model="form.notes" rows="3" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('statisticsMembers.notes')" />
          <div class="flex gap-2">
            <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white" :disabled="saving">{{ t('forms.save') }}</button>
            <button v-if="editingId" type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
          </div>
        </form>
      </div>
    </template>

    <div v-if="showMessage" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <form class="w-full max-w-lg space-y-3 rounded-xl bg-white p-5 shadow-lg" @submit.prevent="send">
        <h3 class="font-semibold text-[var(--rdp-forest)]">{{ t('statisticsMembers.composeTitle') }}</h3>
        <p class="text-sm text-slate-600">
          {{ messageMode === 'filtered' ? t('statisticsMembers.composeFiltered', { n: emailCount }) : t('statisticsMembers.composeSelected', { n: selectedCount }) }}
        </p>
        <input v-model="message.subject" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('forms.subject')" />
        <textarea v-model="message.body" required rows="7" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('forms.message')" />
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded border px-4 py-2 text-sm" @click="showMessage = false">{{ t('forms.cancel') }}</button>
          <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white" :disabled="sending">{{ sending ? t('statisticsMembers.sending') : t('forms.send') }}</button>
        </div>
      </form>
    </div>
  </div>
</template>
