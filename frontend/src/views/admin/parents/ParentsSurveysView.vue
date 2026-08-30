<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createParentSurvey,
  deleteParentSurvey,
  fetchParentSurveyResponses,
  fetchParentSurveys,
  updateParentSurvey,
} from '@/services/parents'

const { t, locale } = useI18n()
const auth = useAuthStore()
const items = ref([])
const error = ref('')
const responses = ref([])
const viewingId = ref(null)
const canManage = () => auth.hasPermission('parents.survey.manage')

const form = reactive({
  title_ar: '',
  title_fr: '',
  details_ar: '',
  details_fr: '',
  form_url: '',
  questions_text: '',
  status: 'published',
})

function questionsFromText(text) {
  return String(text || '')
    .split('\n')
    .map((line) => line.trim())
    .filter(Boolean)
}

async function load() {
  error.value = ''
  try {
    const data = await fetchParentSurveys()
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function save() {
  error.value = ''
  try {
    await createParentSurvey({
      title_ar: form.title_ar,
      title_fr: form.title_fr,
      details_ar: form.details_ar || null,
      details_fr: form.details_fr || null,
      form_url: form.form_url || null,
      questions: questionsFromText(form.questions_text),
      status: form.status,
    })
    Object.assign(form, {
      title_ar: '',
      title_fr: '',
      details_ar: '',
      details_fr: '',
      form_url: '',
      questions_text: '',
      status: 'published',
    })
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function setStatus(item, status) {
  try {
    await updateParentSurvey(item.id, { status })
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function showResponses(item) {
  viewingId.value = item.id
  try {
    const data = await fetchParentSurveyResponses(item.id)
    responses.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('parentsAdmin.surveys') }}</h2>
      <p class="text-sm text-slate-600">{{ t('parentsAdmin.surveysHint') }}</p>
      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border bg-white p-4">
        <p class="font-medium">{{ locale === 'ar' ? item.title_ar : item.title_fr }}</p>
        <p class="mt-1 text-xs text-slate-500">
          {{ t(`parentsAdmin.surveyStatuses.${item.status}`) }}
          · {{ item.responses_count || 0 }} {{ t('parentsAdmin.responses') }}
        </p>
        <div class="mt-2 flex flex-wrap gap-2">
          <button type="button" class="text-xs text-teal-800 hover:underline" @click="showResponses(item)">
            {{ t('parentsAdmin.viewResponses') }}
          </button>
          <select
            v-if="canManage()"
            class="rounded border px-2 py-1 text-xs"
            :value="item.status"
            @change="setStatus(item, $event.target.value)"
          >
            <option value="draft">{{ t('parentsAdmin.surveyStatuses.draft') }}</option>
            <option value="published">{{ t('parentsAdmin.surveyStatuses.published') }}</option>
            <option value="closed">{{ t('parentsAdmin.surveyStatuses.closed') }}</option>
          </select>
          <button
            v-if="canManage()"
            type="button"
            class="text-xs text-rose-700 hover:underline"
            @click="deleteParentSurvey(item.id).then(load)"
          >
            {{ t('forms.delete') }}
          </button>
        </div>
        <div v-if="viewingId === item.id" class="mt-3 space-y-2 border-t pt-3 text-sm">
          <p v-if="!responses.length" class="text-slate-500">{{ t('parentsAdmin.emptyResponses') }}</p>
          <div v-for="row in responses" :key="row.id" class="rounded bg-slate-50 p-2">
            <p class="font-medium">{{ row.parent_name }}</p>
            <p class="text-xs text-slate-500">{{ row.email || row.phone || '—' }}</p>
            <ul class="mt-1 list-disc space-y-1 pe-4 text-slate-700">
              <li v-for="(answer, index) in row.answers || []" :key="index">{{ answer }}</li>
            </ul>
          </div>
        </div>
      </article>
    </div>

    <form v-if="canManage()" class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ t('parentsAdmin.newSurvey') }}</h3>
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleFr')" />
      <input v-model="form.title_ar" required class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('secretariatAdmin.titleAr')" />
      <textarea v-model="form.details_ar" rows="2" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('parentsAdmin.detailsAr')" />
      <textarea v-model="form.details_fr" rows="2" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('parentsAdmin.detailsFr')" />
      <input v-model="form.form_url" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('parentsAdmin.formUrl')" />
      <textarea
        v-model="form.questions_text"
        rows="5"
        class="w-full rounded border px-3 py-2 text-sm"
        :placeholder="t('parentsAdmin.questionsHint')"
      />
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option value="published">{{ t('parentsAdmin.surveyStatuses.published') }}</option>
        <option value="draft">{{ t('parentsAdmin.surveyStatuses.draft') }}</option>
        <option value="closed">{{ t('parentsAdmin.surveyStatuses.closed') }}</option>
      </select>
      <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
    </form>
  </div>
</template>
