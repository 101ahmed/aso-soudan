<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createContactRequest,
  deleteContactRequest,
  fetchContactRequests,
  fetchPartners,
  updateContactRequest,
} from '@/services/external'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const items = ref([])
const partners = ref([])
const editingId = ref(null)
const statuses = ['pending', 'reviewing', 'contacted', 'closed', 'rejected']

const filters = reactive({ search: '', status: '' })
const form = reactive(emptyForm())

const canCreate = computed(() => auth.hasPermission('extcontact.create'))
const canUpdate = computed(() => auth.hasPermission('extcontact.update'))
const canDelete = computed(() => auth.hasPermission('extcontact.delete'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

function emptyForm() {
  return {
    applicant_name: '',
    applicant_phone: '',
    applicant_email: '',
    applicant_organization: '',
    partner_id: '',
    partner_name: '',
    reason: '',
    status: 'pending',
    admin_notes: '',
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
}

function edit(item) {
  editingId.value = item.id
  form.applicant_name = item.applicant_name || ''
  form.applicant_phone = item.applicant_phone || ''
  form.applicant_email = item.applicant_email || ''
  form.applicant_organization = item.applicant_organization || ''
  form.partner_id = item.partner_id || ''
  form.partner_name = item.partner_name || ''
  form.reason = item.reason || ''
  form.status = item.status || 'pending'
  form.admin_notes = item.admin_notes || ''
}

function partnerLabel(item) {
  if (item.partner) {
    return locale.value === 'fr' ? (item.partner.name_fr || item.partner.name_ar) : (item.partner.name_ar || item.partner.name_fr)
  }
  return item.partner_name || ''
}

function statusClass(status) {
  if (status === 'contacted' || status === 'closed') return 'bg-emerald-50 text-emerald-800'
  if (status === 'rejected') return 'bg-rose-50 text-rose-800'
  if (status === 'reviewing') return 'bg-amber-50 text-amber-900'
  return 'bg-slate-100 text-slate-700'
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [rows, orgs] = await Promise.all([
      fetchContactRequests(code.value, {
        search: filters.search || undefined,
        status: filters.status || undefined,
      }),
      fetchPartners(code.value, { per_page: 100 }),
    ])
    items.value = rows.data || []
    partners.value = orgs.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!canManage.value) return
  saving.value = true
  error.value = ''
  try {
    const payload = {
      ...form,
      partner_id: form.partner_id || null,
    }
    if (editingId.value) {
      await updateContactRequest(code.value, editingId.value, payload)
    } else {
      await createContactRequest(code.value, payload)
    }
    resetForm()
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}

async function changeStatus(item, status) {
  if (!canUpdate.value) return
  await updateContactRequest(code.value, item.id, { status })
  await load()
}

async function remove(id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteContactRequest(code.value, id)
  await load()
}

watch([() => filters.search, () => filters.status], load)
onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('externalRel.requestsTitle') }}</h2>
      <p class="text-sm text-slate-600">{{ t('externalRel.requestsHint') }}</p>
      <div class="grid gap-2 sm:grid-cols-2">
        <input v-model="filters.search" :placeholder="t('externalRel.searchRequests')" class="rounded border px-3 py-2 text-sm" />
        <select v-model="filters.status" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('socialHelp.allStatuses') }}</option>
          <option v-for="status in statuses" :key="status" :value="status">{{ t(`externalRel.requestStatus.${status}`) }}</option>
        </select>
      </div>
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs text-slate-500">{{ item.reference }}</p>
            <p class="font-medium">{{ t('externalRel.applicant') }}: {{ item.applicant_name }}</p>
            <p class="mt-1 text-sm text-slate-600">
              <span v-if="item.applicant_phone">{{ item.applicant_phone }}</span>
              <span v-if="item.applicant_organization"> · {{ item.applicant_organization }}</span>
            </p>
            <p v-if="partnerLabel(item)" class="mt-1 text-sm">{{ t('externalRel.targetPartner') }}: {{ partnerLabel(item) }}</p>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">
              <span class="font-semibold">{{ t('externalRel.reason') }}:</span> {{ item.reason }}
            </p>
          </div>
          <div class="flex flex-col items-end gap-2">
            <span class="rounded px-2 py-1 text-xs font-medium" :class="statusClass(item.status)">
              {{ t(`externalRel.requestStatus.${item.status}`) }}
            </span>
            <select
              v-if="canUpdate"
              class="rounded border px-2 py-1 text-xs"
              :value="item.status"
              @change="changeStatus(item, $event.target.value)"
            >
              <option v-for="status in statuses" :key="status" :value="status">{{ t(`externalRel.requestStatus.${status}`) }}</option>
            </select>
            <div class="flex gap-1">
              <button type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">{{ t('forms.edit') }}</button>
              <button
                v-if="canDelete"
                type="button"
                class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700"
                @click="remove(item.id)"
              >
                {{ t('forms.delete') }}
              </button>
            </div>
          </div>
        </div>
      </article>
    </div>

    <form class="space-y-3 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ editingId ? t('externalRel.editRequest') : t('externalRel.newRequest') }}</h3>
      <input v-model="form.applicant_name" required :placeholder="t('externalRel.applicant')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.applicant_phone" :placeholder="t('forms.phone')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.applicant_email" type="email" :placeholder="t('forms.email')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.applicant_organization" :placeholder="t('externalRel.applicantOrg')" class="w-full rounded border px-3 py-2 text-sm" />
      <select v-model="form.partner_id" class="w-full rounded border px-3 py-2 text-sm">
        <option value="">{{ t('externalRel.choosePartner') }}</option>
        <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
      </select>
      <input v-model="form.partner_name" :placeholder="t('externalRel.partnerNameFree')" class="w-full rounded border px-3 py-2 text-sm" />
      <textarea v-model="form.reason" required rows="4" :placeholder="t('externalRel.reason')" class="w-full rounded border px-3 py-2 text-sm" />
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="status in statuses" :key="status" :value="status">{{ t(`externalRel.requestStatus.${status}`) }}</option>
      </select>
      <textarea v-model="form.admin_notes" rows="3" :placeholder="t('socialHelp.adminNotes')" class="w-full rounded border px-3 py-2 text-sm" />
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="!canManage || saving">
          {{ t('forms.save') }}
        </button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
