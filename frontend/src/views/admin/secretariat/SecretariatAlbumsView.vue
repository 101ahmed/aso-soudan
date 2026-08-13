<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickTitle } from '@/utils/localized'
import {
  createAlbum,
  deleteAlbum,
  deleteAlbumMedia,
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
const success = ref('')
const uploading = ref(false)
const canPublish = computed(() => auth.hasPermission('gallery.publish'))
const canManage = computed(() => auth.hasPermission('gallery.manage'))

const form = reactive({
  title_ar: '',
  title_fr: '',
  description_ar: '',
  description_fr: '',
  status: 'draft',
  show_on_home: false,
  show_on_gallery: true,
  cover: null,
})

const mediaAlbumId = ref(null)
const mediaFiles = ref([])
const fileInput = ref(null)

function titleOf(item) {
  return pickTitle(item, locale.value)
}

async function load() {
  try {
    error.value = ''
    const data = await fetchDepartmentAlbums(code.value)
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.userMessage || e.message
  }
}

async function save() {
  try {
    error.value = ''
    success.value = ''
    await createAlbum(code.value, { ...form })
    Object.assign(form, {
      title_ar: '',
      title_fr: '',
      description_ar: '',
      description_fr: '',
      status: canPublish.value ? 'published' : 'draft',
      show_on_home: false,
      show_on_gallery: true,
      cover: null,
    })
    success.value = t('secretariatAdmin.albumCreated')
    await load()
  } catch (e) {
    error.value = formatError(e)
  }
}

function onMediaPick(event) {
  mediaFiles.value = Array.from(event.target.files || [])
  error.value = ''
}

async function openUpload(albumId) {
  mediaAlbumId.value = albumId
  mediaFiles.value = []
  success.value = ''
  error.value = ''
  await nextTick()
  fileInput.value?.click()
}

async function addMedia() {
  if (!mediaAlbumId.value) return
  if (!mediaFiles.value.length) {
    error.value = t('secretariatAdmin.selectFilesFirst')
    fileInput.value?.click()
    return
  }
  uploading.value = true
  try {
    error.value = ''
    success.value = ''
    await uploadAlbumMedia(code.value, mediaAlbumId.value, mediaFiles.value)
    success.value = t('secretariatAdmin.uploadOk')
    mediaFiles.value = []
    if (fileInput.value) fileInput.value.value = ''
    mediaAlbumId.value = null
    await load()
  } catch (e) {
    error.value = formatError(e)
  } finally {
    uploading.value = false
  }
}

async function removeMedia(albumId, mediaId) {
  if (!window.confirm(t('secretariatAdmin.confirmDeletePhoto'))) return
  try {
    error.value = ''
    await deleteAlbumMedia(code.value, albumId, mediaId)
    success.value = t('secretariatAdmin.photoDeleted')
    await load()
  } catch (e) {
    error.value = formatError(e)
  }
}

async function removeAlbum(albumId) {
  if (!window.confirm(t('secretariatAdmin.confirmDeleteAlbum'))) return
  try {
    error.value = ''
    await deleteAlbum(code.value, albumId)
    success.value = t('secretariatAdmin.albumDeleted')
    if (mediaAlbumId.value === albumId) cancelUpload()
    await load()
  } catch (e) {
    error.value = formatError(e)
  }
}

function formatError(e) {
  const errors = e.response?.data?.errors
  if (errors) {
    return Object.values(errors).flat().join(' ')
  }
  return e.response?.data?.message || e.userMessage || e.message
}

function cancelUpload() {
  mediaAlbumId.value = null
  mediaFiles.value = []
  if (fileInput.value) fileInput.value.value = ''
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
      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>

      <article v-for="item in items" :key="item.id" class="rounded-lg border bg-white p-4">
        <div class="flex gap-3">
          <img
            v-if="item.cover_url"
            :src="item.cover_url"
            alt=""
            class="h-16 w-16 rounded object-cover"
          />
          <div class="min-w-0 flex-1">
            <p class="font-medium">{{ titleOf(item) }}</p>
            <p class="mt-1 text-xs text-slate-500">
              {{ (item.media || []).length }} {{ t('secretariatAdmin.photos') }}
              · {{ item.status }}
              <span v-if="item.slug"> · /gallery/{{ item.slug }}</span>
            </p>
          </div>
        </div>

        <div v-if="item.media?.length" class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
          <div v-for="m in item.media" :key="m.id" class="relative overflow-hidden rounded-lg border bg-slate-50">
            <img :src="m.url" alt="" class="h-20 w-full object-cover" />
            <button
              v-if="canManage"
              type="button"
              class="absolute end-1 top-1 rounded bg-rose-600 px-1.5 py-0.5 text-[10px] font-semibold text-white shadow hover:bg-rose-700"
              :title="t('secretariatAdmin.deletePhoto')"
              @click="removeMedia(item.id, m.id)"
            >
              ×
            </button>
          </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
          <button
            v-if="item.status !== 'published' && canPublish"
            type="button"
            class="rounded border px-2 py-1 text-xs"
            @click="publishAlbum(code, item.id).then(load)"
          >
            {{ t('secretariatAdmin.publish') }}
          </button>
          <button
            v-if="canManage"
            type="button"
            class="rounded border px-2 py-1 text-xs"
            @click="openUpload(item.id)"
          >
            {{ t('secretariatAdmin.addPhotos') }}
          </button>
          <button
            v-if="canManage"
            type="button"
            class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700"
            @click="removeAlbum(item.id)"
          >
            {{ t('secretariatAdmin.deleteAlbum') }}
          </button>
        </div>
      </article>

      <div v-if="mediaAlbumId" class="rounded-lg border border-dashed border-teal-300 bg-teal-50/40 p-4">
        <p class="text-sm font-medium">{{ t('secretariatAdmin.uploadToAlbum') }} #{{ mediaAlbumId }}</p>
        <p class="mt-1 text-xs text-slate-600">{{ t('secretariatAdmin.uploadHint') }}</p>
        <input
          ref="fileInput"
          type="file"
          accept="image/jpeg,image/png,image/webp,image/gif"
          multiple
          class="mt-2 w-full text-sm"
          @change="onMediaPick"
        />
        <ul v-if="mediaFiles.length" class="mt-2 list-disc space-y-0.5 pe-5 text-xs text-slate-700">
          <li v-for="(f, i) in mediaFiles" :key="i">{{ f.name }} ({{ Math.round(f.size / 1024) }} KB)</li>
        </ul>
        <div class="mt-2 flex gap-2">
          <button
            type="button"
            class="rounded bg-teal-800 px-3 py-1.5 text-sm text-white disabled:opacity-50"
            :disabled="uploading"
            @click="addMedia"
          >
            {{ uploading ? t('secretariatAdmin.uploading') : t('forms.upload') }}
          </button>
          <button type="button" class="rounded border px-3 py-1.5 text-sm" @click="cancelUpload">
            {{ t('forms.cancel') }}
          </button>
        </div>
      </div>
    </div>

    <form class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ t('secretariatAdmin.newAlbum') }}</h3>
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleFr')" />
      <input v-model="form.title_ar" required class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('secretariatAdmin.titleAr')" />
      <textarea v-model="form.description_fr" rows="2" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.descFr')" />
      <textarea v-model="form.description_ar" rows="2" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('secretariatAdmin.descAr')" />
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option value="draft">draft</option>
        <option v-if="canPublish" value="published">published</option>
      </select>
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.show_on_gallery" type="checkbox" />
        {{ t('secretariatAdmin.showOnGallery') }}
      </label>
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.show_on_home" type="checkbox" />
        {{ t('secretariatAdmin.showOnHome') }}
      </label>
      <div>
        <p class="mb-1 text-xs text-slate-500">{{ t('secretariatAdmin.cover') }}</p>
        <input type="file" accept="image/*" class="w-full text-sm" @change="form.cover = $event.target.files?.[0] || null" />
      </div>
      <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
    </form>
  </div>
</template>
