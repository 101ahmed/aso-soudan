<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickName, pickTitle } from '@/utils/localized'
import {
  createSiteAnnouncement,
  deleteSiteAnnouncement,
  fetchSiteAnnouncements,
  publishSiteAnnouncement,
  updateSiteAnnouncement,
} from '@/services/content'

const { t, locale } = useI18n()
const auth = useAuthStore()
const items = ref([])
const error = ref('')
const editingId = ref(null)
const existingImageUrl = ref('')
const fileInput = ref(null)

const canCreate = computed(() => auth.hasPermission('announcement.create'))
const canUpdate = computed(() => auth.hasPermission('announcement.update'))
const canDelete = computed(() => auth.hasPermission('announcement.delete'))
const canPublish = computed(() => auth.hasPermission('announcement.publish'))

const form = reactive({
  title_ar: '',
  title_fr: '',
  content_ar: '',
  content_fr: '',
  starts_at: '',
  ends_at: '',
  show_on_secretariat: true,
  show_on_home: true,
  status: 'draft',
  image: null,
})

const imagePreview = computed(() => {
  if (form.image) return URL.createObjectURL(form.image)
  return existingImageUrl.value
})

function titleOf(item) {
  return pickTitle(item, locale.value)
}

function departmentOf(item) {
  return pickName(item.department, locale.value) || item.department?.code || '—'
}

function resetForm() {
  editingId.value = null
  existingImageUrl.value = ''
  Object.assign(form, {
    title_ar: '',
    title_fr: '',
    content_ar: '',
    content_fr: '',
    starts_at: '',
    ends_at: '',
    show_on_secretariat: true,
    show_on_home: true,
    status: canPublish.value ? 'published' : 'draft',
    image: null,
  })
  if (fileInput.value) fileInput.value.value = ''
}

async function load() {
  error.value = ''
  try {
    const data = await fetchSiteAnnouncements()
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

function edit(item) {
  editingId.value = item.id
  existingImageUrl.value = item.image_url || ''
  form.title_ar = item.title_ar
  form.title_fr = item.title_fr
  form.content_ar = item.content_ar || ''
  form.content_fr = item.content_fr || ''
  form.starts_at = item.starts_at ? item.starts_at.slice(0, 16) : ''
  form.ends_at = item.ends_at ? item.ends_at.slice(0, 16) : ''
  form.show_on_secretariat = !!item.show_on_secretariat
  form.show_on_home = !!item.show_on_home
  form.status = item.status
  form.image = null
  if (fileInput.value) fileInput.value.value = ''
}

async function save() {
  error.value = ''
  if (!form.image && !existingImageUrl.value) {
    error.value = t('secretariatAdmin.imageRequired')
    return
  }
  try {
    const payload = { ...form }
    if (editingId.value) await updateSiteAnnouncement(editingId.value, payload)
    else await createSiteAnnouncement(payload)
    resetForm()
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors
      ? Object.values(errors).flat().join(' ')
      : e.response?.data?.message || e.message
  }
}

async function publish(id) {
  await publishSiteAnnouncement(id)
  await load()
}

async function remove(id) {
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteSiteAnnouncement(id)
  await load()
}

onMounted(() => {
  resetForm()
  load()
})
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('contentAdmin.announcements') }}</h2>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <p v-else-if="!items.length" class="text-sm text-slate-500">{{ t('contentAdmin.emptyAnnouncements') }}</p>
      <article v-for="item in items" :key="item.id" class="overflow-hidden rounded-lg border bg-white">
        <img
          :src="item.image_url || '/logo.png'"
          alt=""
          class="h-36 w-full bg-slate-50 object-cover"
        />
        <div class="p-4">
          <p class="font-medium">{{ titleOf(item) }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ item.status }} · {{ departmentOf(item) }}</p>
          <p v-if="item.show_on_home" class="mt-1 text-xs font-medium text-teal-800">
            {{ t('contentAdmin.onHome') }}
          </p>
          <div class="mt-2 flex gap-2">
            <button v-if="canUpdate" type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">
              {{ t('forms.edit') }}
            </button>
            <button
              v-if="item.status !== 'published' && canPublish"
              type="button"
              class="rounded border px-2 py-1 text-xs"
              @click="publish(item.id)"
            >
              {{ t('secretariatAdmin.publish') }}
            </button>
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
      </article>
    </div>

    <form
      v-if="canCreate || (editingId && canUpdate)"
      class="space-y-3 rounded-xl border bg-white p-5"
      @submit.prevent="save"
    >
      <h3 class="font-semibold">{{ editingId ? t('secretariatAdmin.editAnnouncement') : t('secretariatAdmin.newAnnouncement') }}</h3>
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleFr')" />
      <input v-model="form.title_ar" required class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('secretariatAdmin.titleAr')" />
      <textarea v-model="form.content_fr" rows="3" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.contentFr')" />
      <textarea v-model="form.content_ar" rows="3" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('secretariatAdmin.contentAr')" />
      <label class="block text-sm">
        <span class="mb-1 block text-xs text-slate-500">{{ t('secretariatAdmin.image') }}</span>
        <input
          ref="fileInput"
          type="file"
          accept="image/*"
          class="w-full text-sm"
          :required="!existingImageUrl"
          @change="form.image = $event.target.files?.[0] || null"
        />
        <span class="mt-1 block text-xs text-slate-500">{{ t('secretariatAdmin.imageHint') }}</span>
      </label>
      <img
        v-if="imagePreview"
        :src="imagePreview"
        alt=""
        class="h-36 w-full rounded-md object-cover"
      />
      <label class="block text-xs text-slate-500">{{ t('secretariatAdmin.startsAt') }}
        <input v-model="form.starts_at" type="datetime-local" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-xs text-slate-500">{{ t('secretariatAdmin.endsAt') }}
        <input v-model="form.ends_at" type="datetime-local" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="flex items-center gap-2 text-sm"><input v-model="form.show_on_secretariat" type="checkbox" /> {{ t('secretariatAdmin.showOnSecretariat') }}</label>
      <label class="flex items-center gap-2 text-sm"><input v-model="form.show_on_home" type="checkbox" /> {{ t('secretariatAdmin.showOnHome') }}</label>
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option value="draft">draft</option>
        <option value="pending_review">pending_review</option>
        <option v-if="canPublish" value="published">published</option>
      </select>
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
