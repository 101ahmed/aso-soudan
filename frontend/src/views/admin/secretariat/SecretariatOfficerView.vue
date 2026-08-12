<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { fetchDepartment, updateDepartmentOfficer } from '@/services/content'

const route = useRoute()
const { t } = useI18n()
const code = computed(() => route.params.code)
const error = ref('')
const success = ref('')
const saving = ref(false)
const photoPreview = ref(null)

const form = reactive({
  officer_name_fr: '',
  officer_name_ar: '',
  officer_title_fr: '',
  officer_title_ar: '',
  officer_bio_fr: '',
  officer_bio_ar: '',
  officer_email: '',
  officer_phone: '',
  officer_is_public: true,
  photo: null,
  remove_photo: false,
})

async function load() {
  error.value = ''
  try {
    const dept = await fetchDepartment(code.value)
    const o = dept.officer || {}
    Object.assign(form, {
      officer_name_fr: o.name_fr || '',
      officer_name_ar: o.name_ar || '',
      officer_title_fr: o.title_fr || '',
      officer_title_ar: o.title_ar || '',
      officer_bio_fr: o.bio_fr || '',
      officer_bio_ar: o.bio_ar || '',
      officer_email: o.email || '',
      officer_phone: o.phone || '',
      officer_is_public: o.is_public !== false,
      photo: null,
      remove_photo: false,
    })
    photoPreview.value = o.photo_url || null
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

function onPhotoPick(event) {
  const file = event.target.files?.[0] || null
  form.photo = file
  form.remove_photo = false
  photoPreview.value = file ? URL.createObjectURL(file) : photoPreview.value
}

function clearPhoto() {
  form.photo = null
  form.remove_photo = true
  photoPreview.value = null
}

async function save() {
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    const dept = await updateDepartmentOfficer(code.value, { ...form })
    success.value = t('secretariatAdmin.officerSaved')
    const o = dept.officer || {}
    photoPreview.value = o.photo_url || null
    form.photo = null
    form.remove_photo = false
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors
      ? Object.values(errors).flat().join(' ')
      : (e.response?.data?.message || e.message)
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-5">
    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('secretariatAdmin.officer') }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t('secretariatAdmin.officerHint') }}</p>
    </div>

    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>

    <form class="space-y-4 rounded-xl border bg-white p-5" @submit.prevent="save">
      <div class="flex flex-wrap items-center gap-4">
        <img
          v-if="photoPreview"
          :src="photoPreview"
          alt=""
          class="h-24 w-24 rounded-2xl object-cover object-top ring-2 ring-[var(--rdp-forest)]/20"
        />
        <div
          v-else
          class="flex h-24 w-24 items-center justify-center rounded-2xl bg-[var(--rdp-forest)] text-2xl font-bold text-white"
        >
          {{ (form.officer_name_ar || form.officer_name_fr || '?').slice(0, 1) }}
        </div>
        <div class="space-y-2">
          <input type="file" accept="image/*" class="block w-full text-sm" @change="onPhotoPick" />
          <button v-if="photoPreview" type="button" class="text-xs text-rose-700 hover:underline" @click="clearPhoto">
            {{ t('secretariatAdmin.removePhoto') }}
          </button>
        </div>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <input v-model="form.officer_name_fr" class="rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.officerNameFr')" />
        <input v-model="form.officer_name_ar" dir="rtl" class="rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.officerNameAr')" />
        <input v-model="form.officer_title_fr" class="rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.officerTitleFr')" />
        <input v-model="form.officer_title_ar" dir="rtl" class="rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.officerTitleAr')" />
        <input v-model="form.officer_email" type="email" class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.email')" />
        <input v-model="form.officer_phone" class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.phoneOptional')" />
      </div>

      <textarea v-model="form.officer_bio_fr" rows="3" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.officerBioFr')" />
      <textarea v-model="form.officer_bio_ar" rows="3" dir="rtl" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.officerBioAr')" />

      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.officer_is_public" type="checkbox" />
        {{ t('secretariatAdmin.officerPublic') }}
      </label>

      <!-- Preview card -->
      <div class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-slate-50 p-5">
        <p class="mb-3 text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ t('secretariatAdmin.officerPreview') }}</p>
        <div class="flex items-center gap-4">
          <img
            v-if="photoPreview"
            :src="photoPreview"
            alt=""
            class="h-16 w-16 rounded-full object-cover object-top"
          />
          <div v-else class="flex h-16 w-16 items-center justify-center rounded-full bg-[var(--rdp-forest)] text-lg font-bold text-white">
            {{ (form.officer_name_ar || form.officer_name_fr || '?').slice(0, 1) }}
          </div>
          <div>
            <p class="font-semibold">{{ form.officer_name_ar || form.officer_name_fr || '—' }}</p>
            <p class="text-sm text-[var(--rdp-forest)]">{{ form.officer_title_ar || form.officer_title_fr }}</p>
          </div>
        </div>
        <p class="mt-3 text-sm text-slate-600">{{ form.officer_bio_ar || form.officer_bio_fr }}</p>
        <p v-if="form.officer_email" class="mt-2 text-sm font-medium">{{ form.officer_email }}</p>
      </div>

      <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="saving">
        {{ saving ? t('academicAttendance.saving') : t('forms.save') }}
      </button>
    </form>
  </div>
</template>
