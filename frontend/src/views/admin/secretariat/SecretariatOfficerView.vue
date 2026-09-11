<script setup>
import { computed, reactive, ref, shallowRef, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { fetchDepartment, updateDepartmentDeputy, updateDepartmentOfficer } from '@/services/content'
import { prepareUploadImage } from '@/utils/prepareUploadImage'

const route = useRoute()
const { t } = useI18n()
const code = computed(() => route.params.code)
const role = computed(() => (route.name === 'admin.secretariat.deputy' ? 'deputy' : 'officer'))
const error = ref('')
const success = ref('')
const saving = ref(false)
const photoPreview = ref(null)
const photoFile = shallowRef(null)
const fileInput = ref(null)

const form = reactive({
  name_fr: '',
  name_ar: '',
  title_fr: '',
  title_ar: '',
  bio_fr: '',
  bio_ar: '',
  email: '',
  phone: '',
  is_public: true,
  remove_photo: false,
})

function applyCard(card) {
  const o = card || {}
  Object.assign(form, {
    name_fr: o.name_fr || '',
    name_ar: o.name_ar || '',
    title_fr: o.title_fr || '',
    title_ar: o.title_ar || '',
    bio_fr: o.bio_fr || '',
    bio_ar: o.bio_ar || '',
    email: o.email || '',
    phone: o.phone || '',
    is_public: o.is_public !== false,
    remove_photo: false,
  })
  photoFile.value = null
  photoPreview.value = o.photo_url || null
  if (fileInput.value) fileInput.value.value = ''
}

async function load() {
  error.value = ''
  success.value = ''
  try {
    const dept = await fetchDepartment(code.value)
    applyCard(role.value === 'deputy' ? dept.deputy : dept.officer)
  } catch (e) {
    error.value = e.response?.data?.message || e.message
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
  const prefix = role.value
  const payload = {
    [`${prefix}_name_fr`]: form.name_fr,
    [`${prefix}_name_ar`]: form.name_ar,
    [`${prefix}_title_fr`]: form.title_fr,
    [`${prefix}_title_ar`]: form.title_ar,
    [`${prefix}_bio_fr`]: form.bio_fr,
    [`${prefix}_bio_ar`]: form.bio_ar,
    [`${prefix}_email`]: form.email,
    [`${prefix}_phone`]: form.phone,
    [`${prefix}_is_public`]: form.is_public,
    photo: photoFile.value,
    remove_photo: form.remove_photo,
  }
  try {
    const updater = role.value === 'deputy' ? updateDepartmentDeputy : updateDepartmentOfficer
    const dept = await updater(code.value, payload)
    success.value = t(`secretariatAdmin.${role.value}Saved`)
    applyCard(role.value === 'deputy' ? dept.deputy : dept.officer)
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors
      ? Object.values(errors).flat().join(' ')
      : (e.response?.data?.message || e.message)
  } finally {
    saving.value = false
  }
}

watch(() => [code.value, role.value, route.fullPath], load, { immediate: true })
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-5">
    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t(`secretariatAdmin.${role}`) }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t(`secretariatAdmin.${role}Hint`) }}</p>
    </div>

    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>

    <form class="space-y-4 rounded-xl border bg-white p-5" @submit.prevent="save">
      <div class="flex flex-wrap items-center gap-4">
        <img
          v-if="photoPreview"
          :key="photoPreview"
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
            {{ t('secretariatAdmin.removePhoto') }}
          </button>
        </div>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <input v-model="form.name_fr" class="rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.officerNameFr')" />
        <input v-model="form.name_ar" dir="rtl" class="rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.officerNameAr')" />
        <input v-model="form.title_fr" class="rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.officerTitleFr')" />
        <input v-model="form.title_ar" dir="rtl" class="rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.officerTitleAr')" />
        <input v-model="form.email" type="email" class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.email')" />
        <input v-model="form.phone" class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.phoneOptional')" />
      </div>

      <textarea v-model="form.bio_fr" rows="3" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.officerBioFr')" />
      <textarea v-model="form.bio_ar" rows="3" dir="rtl" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.officerBioAr')" />

      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.is_public" type="checkbox" />
        {{ t('secretariatAdmin.officerPublic') }}
      </label>

      <div class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-slate-50 p-5">
        <p class="mb-3 text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ t('secretariatAdmin.officerPreview') }}</p>
        <div class="flex items-center gap-4">
          <img
            v-if="photoPreview"
            :key="`preview-${photoPreview}`"
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
            <p class="text-sm text-[var(--rdp-forest)]">{{ form.title_ar || form.title_fr }}</p>
          </div>
        </div>
        <p class="mt-3 text-sm text-slate-600">{{ form.bio_ar || form.bio_fr }}</p>
        <p v-if="form.email" class="mt-2 text-sm font-medium">{{ form.email }}</p>
      </div>

      <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="saving">
        {{ saving ? t('academicAttendance.saving') : t('forms.save') }}
      </button>
    </form>
  </div>
</template>
