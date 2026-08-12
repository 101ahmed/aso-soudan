<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createAnnouncement,
  deleteAnnouncement,
  fetchDepartmentAnnouncements,
  publishAnnouncement,
  updateAnnouncement,
} from '@/services/content'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const items = ref([])
const error = ref('')
const editingId = ref(null)
const canPublish = computed(() => auth.hasPermission('announcement.publish'))

const form = reactive({
  title_ar: '',
  title_fr: '',
  content_ar: '',
  content_fr: '',
  starts_at: '',
  ends_at: '',
  show_on_secretariat: true,
  show_on_home: false,
  status: 'draft',
})

function titleOf(item) {
  return locale.value === 'ar' ? item.title_ar : item.title_fr
}

function resetForm() {
  editingId.value = null
  Object.assign(form, {
    title_ar: '',
    title_fr: '',
    content_ar: '',
    content_fr: '',
    starts_at: '',
    ends_at: '',
    show_on_secretariat: true,
    show_on_home: false,
    status: canPublish.value ? 'published' : 'draft',
  })
}

async function load() {
  error.value = ''
  try {
    const data = await fetchDepartmentAnnouncements(code.value)
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

function edit(item) {
  editingId.value = item.id
  form.title_ar = item.title_ar
  form.title_fr = item.title_fr
  form.content_ar = item.content_ar || ''
  form.content_fr = item.content_fr || ''
  form.starts_at = item.starts_at ? item.starts_at.slice(0, 16) : ''
  form.ends_at = item.ends_at ? item.ends_at.slice(0, 16) : ''
  form.show_on_secretariat = !!item.show_on_secretariat
  form.show_on_home = !!item.show_on_home
  form.status = item.status
}

async function save() {
  try {
    const payload = { ...form }
    if (editingId.value) await updateAnnouncement(code.value, editingId.value, payload)
    else await createAnnouncement(code.value, payload)
    resetForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

onMounted(() => {
  resetForm()
  load()
})
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('secretariatAdmin.announcements') }}</h2>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border bg-white p-4">
        <p class="font-medium">{{ titleOf(item) }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ item.status }}</p>
        <div class="mt-2 flex gap-2">
          <button type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">{{ t('forms.edit') }}</button>
          <button
            v-if="item.status !== 'published' && canPublish"
            type="button"
            class="rounded border px-2 py-1 text-xs"
            @click="publishAnnouncement(code, item.id).then(load)"
          >
            {{ t('secretariatAdmin.publish') }}
          </button>
          <button
            type="button"
            class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700"
            @click="deleteAnnouncement(code, item.id).then(load)"
          >
            {{ t('forms.delete') }}
          </button>
        </div>
      </article>
    </div>

    <form class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ editingId ? t('secretariatAdmin.editAnnouncement') : t('secretariatAdmin.newAnnouncement') }}</h3>
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleFr')" />
      <input v-model="form.title_ar" required class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('secretariatAdmin.titleAr')" />
      <textarea v-model="form.content_fr" rows="3" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.contentFr')" />
      <textarea v-model="form.content_ar" rows="3" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('secretariatAdmin.contentAr')" />
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
      <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
    </form>
  </div>
</template>
