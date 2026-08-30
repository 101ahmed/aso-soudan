<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { createPartner, deletePartner, fetchPartners, updatePartner } from '@/services/external'

const route = useRoute()
const { t } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const items = ref([])
const editingId = ref(null)

const types = ['association', 'institution', 'municipality', 'cultural', 'other']
const statuses = ['prospect', 'active', 'paused', 'ended']

const filters = reactive({ search: '', type: '', partnership_status: '' })
const form = reactive(emptyForm())

const canCreate = computed(() => auth.hasPermission('partner.create'))
const canUpdate = computed(() => auth.hasPermission('partner.update'))
const canDelete = computed(() => auth.hasPermission('partner.delete'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

function emptyForm() {
  return {
    name_ar: '',
    name_fr: '',
    type: 'association',
    city: '',
    website: '',
    email: '',
    phone: '',
    description_ar: '',
    description_fr: '',
    partnership_status: 'prospect',
    is_public: false,
    notes: '',
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
}

function edit(item) {
  editingId.value = item.id
  form.name_ar = item.name_ar || ''
  form.name_fr = item.name_fr || ''
  form.type = item.type || 'association'
  form.city = item.city || ''
  form.website = item.website || ''
  form.email = item.email || ''
  form.phone = item.phone || ''
  form.description_ar = item.description_ar || ''
  form.description_fr = item.description_fr || ''
  form.partnership_status = item.partnership_status || 'prospect'
  form.is_public = !!item.is_public
  form.notes = item.notes || ''
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchPartners(code.value, {
      search: filters.search || undefined,
      type: filters.type || undefined,
      partnership_status: filters.partnership_status || undefined,
    })
    items.value = data.data || []
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
    if (editingId.value) {
      await updatePartner(code.value, editingId.value, form)
    } else {
      await createPartner(code.value, form)
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

async function remove(id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deletePartner(code.value, id)
  await load()
}

watch([() => filters.search, () => filters.type, () => filters.partnership_status], load)
onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('externalRel.partnersTitle') }}</h2>
      <p class="text-sm text-slate-600">{{ t('externalRel.partnersHint') }}</p>
      <div class="grid gap-2 sm:grid-cols-3">
        <input v-model="filters.search" :placeholder="t('externalRel.searchPartners')" class="rounded border px-3 py-2 text-sm" />
        <select v-model="filters.type" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('externalRel.allTypes') }}</option>
          <option v-for="type in types" :key="type" :value="type">{{ t(`externalRel.types.${type}`) }}</option>
        </select>
        <select v-model="filters.partnership_status" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('externalRel.allStatuses') }}</option>
          <option v-for="status in statuses" :key="status" :value="status">{{ t(`externalRel.partnership.${status}`) }}</option>
        </select>
      </div>
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="font-medium">{{ item.name_ar }}</p>
            <p v-if="item.name_fr" class="text-sm text-slate-500">{{ item.name_fr }}</p>
            <p class="mt-1 text-sm text-slate-600">
              {{ t(`externalRel.types.${item.type}`) }}
              <span v-if="item.city"> · {{ item.city }}</span>
              <span v-if="item.documents_count"> · {{ item.documents_count }} {{ t('externalRel.files') }}</span>
            </p>
            <p v-if="item.website" class="mt-1 text-xs">
              <a :href="item.website" class="text-teal-800 hover:underline" target="_blank" rel="noreferrer">{{ item.website }}</a>
            </p>
          </div>
          <div class="flex flex-col items-end gap-2">
            <span class="rounded bg-slate-100 px-2 py-1 text-xs">{{ t(`externalRel.partnership.${item.partnership_status}`) }}</span>
            <span v-if="item.is_public" class="text-xs text-emerald-700">{{ t('externalRel.public') }}</span>
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
      <h3 class="font-semibold">{{ editingId ? t('externalRel.editPartner') : t('externalRel.newPartner') }}</h3>
      <input v-model="form.name_ar" required :placeholder="t('externalRel.nameAr')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.name_fr" :placeholder="t('externalRel.nameFr')" class="w-full rounded border px-3 py-2 text-sm" />
      <select v-model="form.type" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="type in types" :key="type" :value="type">{{ t(`externalRel.types.${type}`) }}</option>
      </select>
      <select v-model="form.partnership_status" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="status in statuses" :key="status" :value="status">{{ t(`externalRel.partnership.${status}`) }}</option>
      </select>
      <input v-model="form.city" :placeholder="t('forms.city')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.website" :placeholder="t('externalRel.website')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.email" type="email" :placeholder="t('forms.email')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.phone" :placeholder="t('forms.phone')" class="w-full rounded border px-3 py-2 text-sm" />
      <textarea v-model="form.description_ar" rows="3" :placeholder="t('externalRel.descAr')" class="w-full rounded border px-3 py-2 text-sm" />
      <textarea v-model="form.description_fr" rows="2" :placeholder="t('externalRel.descFr')" class="w-full rounded border px-3 py-2 text-sm" />
      <textarea v-model="form.notes" rows="2" :placeholder="t('externalRel.internalNotes')" class="w-full rounded border px-3 py-2 text-sm" />
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.is_public" type="checkbox" />
        {{ t('externalRel.showPublic') }}
      </label>
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="!canManage || saving">
          {{ t('forms.save') }}
        </button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
