<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createAlbum,
  deleteAlbum,
  fetchDepartmentAlbums,
  publishAlbum,
  uploadAlbumMedia,
} from '@/services/content'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const items = ref([])
const error = ref('')
const canPublish = computed(() => auth.hasPermission('gallery.publish'))

const form = reactive({
  title_ar: '',
  title_fr: '',
  description_ar: '',
  description_fr: '',
  status: 'draft',
  cover: null,
})

const mediaAlbumId = ref(null)
const mediaFile = ref(null)

function titleOf(item) {
  return locale.value === 'ar' ? item.title_ar : item.title_fr
}

async function load() {
  try {
    const data = await fetchDepartmentAlbums(code.value)
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function save() {
  try {
    await createAlbum(code.value, { ...form })
    Object.assign(form, {
      title_ar: '',
      title_fr: '',
      description_ar: '',
      description_fr: '',
      status: canPublish.value ? 'published' : 'draft',
      cover: null,
    })
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function addMedia() {
  if (!mediaAlbumId.value || !mediaFile.value) return
  await uploadAlbumMedia(code.value, mediaAlbumId.value, mediaFile.value)
  mediaFile.value = null
  await load()
}

onMounted(() => {
  form.status = canPublish.value ? 'published' : 'draft'
  load()
})
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('secretariatAdmin.albums') }}</h2>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border bg-white p-4">
        <p class="font-medium">{{ titleOf(item) }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ item.status }} · {{ (item.media || []).length }} photos</p>
        <div class="mt-2 flex flex-wrap gap-2">
          <button
            v-if="item.status !== 'published' && canPublish"
            type="button"
            class="rounded border px-2 py-1 text-xs"
            @click="publishAlbum(code, item.id).then(load)"
          >
            {{ t('secretariatAdmin.publish') }}
          </button>
          <button type="button" class="rounded border px-2 py-1 text-xs" @click="mediaAlbumId = item.id">
            {{ t('secretariatAdmin.addPhotos') }}
          </button>
          <button
            type="button"
            class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700"
            @click="deleteAlbum(code, item.id).then(load)"
          >
            {{ t('forms.delete') }}
          </button>
        </div>
      </article>

      <div v-if="mediaAlbumId" class="rounded-lg border border-dashed p-4">
        <p class="text-sm font-medium">{{ t('secretariatAdmin.uploadToAlbum') }} #{{ mediaAlbumId }}</p>
        <input type="file" accept="image/*" class="mt-2 w-full text-sm" @change="mediaFile = $event.target.files?.[0] || null" />
        <button type="button" class="mt-2 rounded bg-teal-800 px-3 py-1.5 text-sm text-white" @click="addMedia">
          {{ t('forms.upload') }}
        </button>
      </div>
    </div>

    <form class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ t('secretariatAdmin.newAlbum') }}</h3>
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleFr')" />
      <input v-model="form.title_ar" required class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('secretariatAdmin.titleAr')" />
      <textarea v-model="form.description_fr" rows="2" class="w-full rounded border px-3 py-2 text-sm" />
      <textarea v-model="form.description_ar" rows="2" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" />
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option value="draft">draft</option>
        <option v-if="canPublish" value="published">published</option>
      </select>
      <input type="file" accept="image/*" class="w-full text-sm" @change="form.cover = $event.target.files?.[0] || null" />
      <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
    </form>
  </div>
</template>
