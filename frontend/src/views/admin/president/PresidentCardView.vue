<script setup>
import { computed, reactive, ref, shallowRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { fetchPresidentCard, fetchVicePresidentCard, updatePresidentCard, updateVicePresidentCard } from '@/services/president'
import { prepareUploadImage } from '@/utils/prepareUploadImage'

const props = defineProps({
  office: {
    type: String,
    default: 'president',
  },
})

const { t } = useI18n()
const isVice = computed(() => props.office === 'vice_president')
const i18nRoot = computed(() => (isVice.value ? 'vicePresidentAdmin' : 'presidentAdmin'))
const roleLabel = computed(() => t(isVice.value ? 'org.vicePresident' : 'org.president'))
const error = ref('')
const success = ref('')
const saving = ref(false)
const loading = ref(false)
const photoPreview = ref(null)
const photoFile = shallowRef(null)
const fileInput = ref(null)

const form = reactive({
  name_fr: '',
  name_ar: '',
  is_public: true,
  remove_photo: false,
})

function applyCard(card) {
  const o = card || {}
  Object.assign(form, {
    name_fr: o.name_fr || '',
    name_ar: o.name_ar || '',
    is_public: o.is_public !== false,
    remove_photo: false,
  })
  photoFile.value = null
  photoPreview.value = o.photo_url || null
  if (fileInput.value) fileInput.value.value = ''
}

async function load() {
  loading.value = true
  error.value = ''
  success.value = ''
  try {
    applyCard(await (isVice.value ? fetchVicePresidentCard() : fetchPresidentCard()))
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function onPhotoPick(event) {
  const file = event.target.files?.[0] || null
  form.remove_photo = false
  if (!file) {
    photoFile.value = null
    return
  }
  try {
    photoFile.value = await prepareUploadImage(file)
    photoPreview.value = URL.createObjectURL(photoFile.value)
  } catch {
    photoFile.value = file
    photoPreview.value = URL.createObjectURL(file)
  }
}

function onPhotoError() {
  if (!photoFile.value) {
    photoPreview.value = null
  }
}

function clearPhoto() {
  photoFile.value = null
  form.remove_photo = true
  photoPreview.value = null
  if (fileInput.value) fileInput.value.value = ''
}

async function save() {
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    const updater = isVice.value ? updateVicePresidentCard : updatePresidentCard
    const card = await updater({
      name_fr: form.name_fr,
      name_ar: form.name_ar,
      is_public: form.is_public,
      photo: photoFile.value,
      remove_photo: form.remove_photo,
    })
    success.value = t(`${i18nRoot.value}.cardSaved`)
    applyCard(card)
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors
      ? Object.values(errors).flat().join(' ')
      : (e.response?.data?.message || e.message)
  } finally {
    saving.value = false
  }
}

load()
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-5">
    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t(`${i18nRoot}.card`) }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t(`${i18nRoot}.cardHint`) }}</p>
    </div>

    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>
    <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>

    <form class="space-y-4 rounded-xl border bg-white p-5" @submit.prevent="save">
      <div class="flex flex-wrap items-center gap-4">
        <img
          v-if="photoPreview"
          :src="photoPreview"
          alt=""
          class="h-24 w-24 rounded-2xl object-cover object-top ring-2 ring-[var(--rdp-forest)]/20"
          @error="onPhotoError"
        />
        <div
          v-else
          class="flex h-24 w-24 items-center justify-center rounded-2xl bg-[var(--rdp-forest)] text-2xl font-bold text-white"
        >
          {{ (form.name_ar || form.name_fr || '?').slice(0, 1) }}
        </div>
        <div class="space-y-2">
          <input ref="fileInput" type="file" accept="image/*" class="block w-full text-sm" @change="onPhotoPick" />
          <button v-if="photoPreview" type="button" class="text-xs text-rose-700 hover:underline" @click="clearPhoto">
            {{ t(`${i18nRoot}.removePhoto`) }}
          </button>
        </div>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <input v-model="form.name_ar" dir="rtl" class="rounded border px-3 py-2 text-sm" :placeholder="t(`${i18nRoot}.nameAr`)" />
        <input v-model="form.name_fr" class="rounded border px-3 py-2 text-sm" :placeholder="t(`${i18nRoot}.nameFr`)" />
      </div>

      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.is_public" type="checkbox" />
        {{ t(`${i18nRoot}.cardPublic`) }}
      </label>

      <div class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-slate-50 p-5">
        <p class="mb-3 text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ t(`${i18nRoot}.cardPreview`) }}</p>
        <div class="flex items-center gap-4">
          <img
            v-if="photoPreview"
            :src="photoPreview"
            alt=""
            class="h-16 w-16 rounded-full object-cover object-top"
            @error="onPhotoError"
          />
          <div v-else class="flex h-16 w-16 items-center justify-center rounded-full bg-[var(--rdp-forest)] text-lg font-bold text-white">
            {{ (form.name_ar || form.name_fr || '?').slice(0, 1) }}
          </div>
          <div>
            <p class="font-semibold">{{ form.name_ar || form.name_fr || '—' }}</p>
            <p class="text-sm text-[var(--rdp-forest)]">{{ roleLabel }}</p>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="saving">
          {{ saving ? t('admin.saving') : t('forms.save') }}
        </button>
        <a href="/president" class="text-sm font-semibold text-teal-800 hover:underline">{{ t(`${i18nRoot}.openPublicPage`) }}</a>
      </div>
    </form>
  </div>
</template>
