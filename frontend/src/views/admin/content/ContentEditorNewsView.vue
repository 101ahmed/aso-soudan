<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickName, pickTitle } from '@/utils/localized'
import {
  archiveSiteNews,
  createSiteNews,
  deleteSiteNews,
  fetchSiteNews,
  publishSiteNews,
  updateSiteNews,
} from '@/services/content'

const { t, locale } = useI18n()
const auth = useAuthStore()
const items = ref([])
const loading = ref(false)
const error = ref('')
const editingId = ref(null)

const canCreate = computed(() => auth.hasPermission('news.create'))
const canUpdate = computed(() => auth.hasPermission('news.update'))
const canDelete = computed(() => auth.hasPermission('news.delete'))
const canPublish = computed(() => auth.hasPermission('news.publish'))

const form = reactive({
  title_ar: '',
  title_fr: '',
  content_ar: '',
  content_fr: '',
  status: 'draft',
  is_featured: false,
  show_on_home: true,
  image: null,
})

function titleOf(item) {
  return pickTitle(item, locale.value)
}

function departmentOf(item) {
  return pickName(item.department, locale.value) || item.department?.code || '—'
}

function resetForm() {
  editingId.value = null
  form.title_ar = ''
  form.title_fr = ''
  form.content_ar = ''
  form.content_fr = ''
  form.status = canPublish.value ? 'published' : 'draft'
  form.is_featured = false
  form.show_on_home = true
  form.image = null
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchSiteNews()
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

function edit(item) {
  editingId.value = item.id
  form.title_ar = item.title_ar
  form.title_fr = item.title_fr
  form.content_ar = item.content_ar || ''
  form.content_fr = item.content_fr || ''
  form.status = item.status
  form.is_featured = !!item.is_featured
  form.show_on_home = !!item.show_on_home
  form.image = null
}

async function save() {
  error.value = ''
  try {
    const payload = { ...form }
    if (editingId.value) {
      await updateSiteNews(editingId.value, payload)
    } else {
      await createSiteNews(payload)
    }
    resetForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function publish(id) {
  await publishSiteNews(id)
  await load()
}

async function archive(id) {
  await archiveSiteNews(id)
  await load()
}

async function remove(id) {
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteSiteNews(id)
  await load()
}

onMounted(() => {
  resetForm()
  load()
})
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.1fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('contentAdmin.news') }}</h2>
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <p v-else-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('contentAdmin.emptyNews') }}</p>
      <article
        v-for="item in items"
        :key="item.id"
        class="rounded-lg border border-slate-200 bg-white p-4"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="font-medium">{{ titleOf(item) }}</p>
            <p class="mt-1 text-xs text-slate-500">
              {{ item.status }} · {{ departmentOf(item) }} · {{ item.published_at || '—' }}
            </p>
            <p v-if="item.show_on_home" class="mt-1 text-xs font-medium text-teal-800">
              {{ t('contentAdmin.onHome') }}
            </p>
          </div>
          <div class="flex flex-wrap gap-1">
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
            <button v-if="canUpdate" type="button" class="rounded border px-2 py-1 text-xs" @click="archive(item.id)">
              {{ t('secretariatAdmin.archive') }}
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
      class="space-y-3 rounded-xl border border-slate-200 bg-white p-5"
      @submit.prevent="save"
    >
      <h3 class="font-semibold">
        {{ editingId ? t('secretariatAdmin.editNews') : t('secretariatAdmin.newNews') }}
      </h3>
      <input v-model="form.title_fr" required :placeholder="t('secretariatAdmin.titleFr')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.title_ar" required :placeholder="t('secretariatAdmin.titleAr')" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" />
      <textarea v-model="form.content_fr" rows="4" :placeholder="t('secretariatAdmin.contentFr')" class="w-full rounded border px-3 py-2 text-sm" />
      <textarea v-model="form.content_ar" rows="4" :placeholder="t('secretariatAdmin.contentAr')" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" />
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option value="draft">draft</option>
        <option value="pending_review">pending_review</option>
        <option v-if="canPublish" value="published">published</option>
        <option value="archived">archived</option>
      </select>
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.is_featured" type="checkbox" />
        {{ t('secretariatAdmin.featured') }}
      </label>
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.show_on_home" type="checkbox" />
        {{ t('secretariatAdmin.showOnHome') }}
      </label>
      <input type="file" accept="image/*" class="w-full text-sm" @change="form.image = $event.target.files?.[0] || null" />
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
