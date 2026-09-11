<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createParentMember,
  deleteParentMember,
  fetchParentMembers,
  updateParentMember,
} from '@/services/parents'

const { t, locale } = useI18n()
const auth = useAuthStore()
const items = ref([])
const error = ref('')
const editingId = ref(null)
const canManage = () => auth.hasPermission('parents.member.manage')

const positions = ['president', 'vice_president', 'treasurer', 'secretary', 'member']

const form = reactive(emptyForm())

function emptyForm() {
  return {
    first_name: '',
    last_name: '',
    position_code: 'president',
    position_ar: '',
    position_fr: '',
    bio_ar: '',
    bio_fr: '',
    status: 'active',
    is_public: true,
    sort_order: 10,
    email: '',
    phone: '',
    photo: null,
  }
}

function positionLabel(item) {
  const key = `parentsAdmin.positions.${item.position_code}`
  const translated = t(key)
  if (translated !== key) return translated
  return locale.value === 'ar'
    ? item.position_ar || item.position_code
    : item.position_fr || item.position_code
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
}

async function load() {
  try {
    const data = await fetchParentMembers()
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

function edit(item) {
  editingId.value = item.id
  Object.assign(form, {
    first_name: item.first_name,
    last_name: item.last_name,
    position_code: item.position_code,
    position_ar: item.position_ar || '',
    position_fr: item.position_fr || '',
    bio_ar: item.bio_ar || '',
    bio_fr: item.bio_fr || '',
    status: item.status,
    is_public: !!item.is_public,
    sort_order: item.sort_order || 10,
    email: item.email || '',
    phone: item.phone || '',
    photo: null,
  })
}

async function save() {
  try {
    if (editingId.value) await updateParentMember(editingId.value, { ...form })
    else await createParentMember({ ...form })
    resetForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('parentsAdmin.bureau') }}</h2>
      <p class="text-sm text-slate-600">{{ t('parentsAdmin.bureauHint') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border bg-white p-4">
        <div class="flex gap-3">
          <img
            v-if="item.photo_url"
            :src="item.photo_url"
            alt=""
            class="h-12 w-12 rounded-full object-cover"
          />
          <div v-else class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-800 text-white">
            {{ item.first_name?.slice(0, 1) }}
          </div>
          <div>
            <p class="font-medium">{{ item.full_name }}</p>
            <p class="text-xs text-slate-500">{{ positionLabel(item) }} · {{ item.status }}</p>
          </div>
        </div>
        <div v-if="canManage()" class="mt-2 flex gap-2">
          <button type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">{{ t('forms.edit') }}</button>
          <button type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="deleteParentMember(item.id).then(load)">{{ t('forms.delete') }}</button>
        </div>
      </article>
    </div>

    <form v-if="canManage()" class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ editingId ? t('parentsAdmin.editOfficer') : t('parentsAdmin.newOfficer') }}</h3>
      <div class="grid grid-cols-2 gap-2">
        <input v-model="form.first_name" required class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.firstName')" />
        <input v-model="form.last_name" required class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.lastName')" />
      </div>
      <select v-model="form.position_code" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="code in positions" :key="code" :value="code">{{ t(`parentsAdmin.positions.${code}`) }}</option>
      </select>
      <input v-model="form.position_fr" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('parentsAdmin.positionFr')" />
      <input v-model="form.position_ar" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('parentsAdmin.positionAr')" />
      <textarea v-model="form.bio_fr" rows="2" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('parentsAdmin.bioFr')" />
      <textarea v-model="form.bio_ar" rows="2" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('parentsAdmin.bioAr')" />
      <input v-model="form.email" type="email" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('forms.email')" />
      <input v-model="form.phone" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('forms.phone')" />
      <label class="flex items-center gap-2 text-sm"><input v-model="form.is_public" type="checkbox" /> {{ t('parentsAdmin.showPublic') }}</label>
      <input type="file" accept="image/*" class="w-full text-sm" @change="form.photo = $event.target.files?.[0] || null" />
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
        <button v-if="editingId" type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
