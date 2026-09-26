<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { fetchPublicStudentCatalog, registerPublicStudent } from '@/services/publicStudents'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const step = ref(1)
const submitted = ref(false)
const sending = ref(false)
const error = ref('')
const catalog = ref({ stages: [], subjects: [] })

const form = reactive({
  student_first_name: '',
  student_last_name: '',
  birth_date: '',
  guardian_name: '',
  guardian_email: '',
  guardian_phone: '',
  education_stage_id: '',
  level_id: '',
  subject_ids: [],
  consent: false,
})

const steps = computed(() => [
  t('register.student.steps.student'),
  t('register.student.steps.guardian'),
  t('register.student.steps.academic'),
  t('register.student.steps.subjects'),
  t('register.student.steps.consent'),
  t('register.student.steps.review'),
])

const levels = computed(() => {
  const stage = (catalog.value.stages || []).find((item) => String(item.id) === String(form.education_stage_id))
  return stage?.levels || []
})

const selectedLevel = computed(() => levels.value.find((item) => String(item.id) === String(form.level_id)))
const selectedSubjects = computed(() =>
  (catalog.value.subjects || []).filter((item) => form.subject_ids.includes(item.id)),
)

function label(item) {
  return pickName(item, locale.value)
}

function canNext() {
  if (step.value === 1) return form.student_first_name.trim() && form.student_last_name.trim()
  if (step.value === 2) return form.guardian_name.trim() && form.guardian_email.trim()
  if (step.value === 3) return Boolean(form.level_id)
  if (step.value === 5) return form.consent
  return true
}

function next() {
  if (step.value < 6 && canNext()) step.value += 1
}

function prev() {
  if (step.value > 1) step.value -= 1
}

function toggleSubject(id) {
  const value = Number(id)
  if (form.subject_ids.includes(value)) form.subject_ids = form.subject_ids.filter((item) => item !== value)
  else form.subject_ids.push(value)
}

async function submit() {
  if (!form.consent || sending.value || !form.level_id) return
  sending.value = true
  error.value = ''
  try {
    await registerPublicStudent({
      first_name: form.student_first_name.trim(),
      last_name: form.student_last_name.trim(),
      birth_date: form.birth_date || null,
      guardian_name: form.guardian_name.trim(),
      guardian_email: form.guardian_email.trim(),
      guardian_phone: form.guardian_phone.trim() || null,
      level_id: Number(form.level_id),
      subject_ids: form.subject_ids,
      consent: true,
    })
    submitted.value = true
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || t('register.student.error')
  } finally {
    sending.value = false
  }
}

onMounted(async () => {
  try {
    catalog.value = await fetchPublicStudentCatalog()
    const firstStage = catalog.value.stages?.[0]
    if (firstStage) form.education_stage_id = firstStage.id
  } catch {
    catalog.value = { stages: [], subjects: [] }
  }
})
</script>

<template>
  <div>
    <PageHero :title="t('register.student.title')" :subtitle="t('register.student.subtitle')" />
    <section class="mx-auto max-w-2xl px-5 py-12 md:px-8">
      <div v-if="submitted" class="rounded-xl bg-white p-6 text-[var(--rdp-forest)]">
        <p class="font-semibold">{{ t('register.student.successTitle') }}</p>
        <p class="mt-2 text-sm">{{ t('register.student.successText') }}</p>
      </div>

      <div v-else class="rounded-xl bg-white p-6 shadow-sm">
        <p class="mb-4 text-sm text-slate-500">
          {{ t('register.step') }} {{ step }} / 6 — {{ steps[step - 1] }}
        </p>
        <p v-if="error" class="mb-4 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

        <div v-if="step === 1" class="space-y-3">
          <input v-model="form.student_first_name" required :placeholder="t('forms.firstName')" class="w-full rounded border px-3 py-2" />
          <input v-model="form.student_last_name" required :placeholder="t('forms.lastName')" class="w-full rounded border px-3 py-2" />
          <input v-model="form.birth_date" type="date" class="w-full rounded border px-3 py-2" />
        </div>
        <div v-else-if="step === 2" class="space-y-3">
          <input v-model="form.guardian_name" required :placeholder="t('forms.guardianName')" class="w-full rounded border px-3 py-2" />
          <input v-model="form.guardian_email" type="email" required :placeholder="t('forms.email')" class="w-full rounded border px-3 py-2" />
          <input v-model="form.guardian_phone" :placeholder="t('forms.phone')" class="w-full rounded border px-3 py-2" />
        </div>
        <div v-else-if="step === 3" class="space-y-3">
          <select v-model="form.education_stage_id" class="w-full rounded border px-3 py-2" @change="form.level_id = ''">
            <option value="">{{ t('register.student.stage') }}</option>
            <option v-for="stage in catalog.stages" :key="stage.id" :value="stage.id">{{ label(stage) }}</option>
          </select>
          <select v-model="form.level_id" required class="w-full rounded border px-3 py-2">
            <option value="">{{ t('register.student.level') }}</option>
            <option v-for="level in levels" :key="level.id" :value="level.id">{{ label(level) }}</option>
          </select>
        </div>
        <div v-else-if="step === 4" class="space-y-3">
          <label v-for="subject in catalog.subjects" :key="subject.id" class="flex items-center gap-2 text-sm">
            <input type="checkbox" :checked="form.subject_ids.includes(subject.id)" @change="toggleSubject(subject.id)" />
            {{ label(subject) }}
          </label>
          <p v-if="!(catalog.subjects || []).length" class="text-sm text-slate-500">{{ t('register.student.noSubjects') }}</p>
        </div>
        <div v-else-if="step === 5" class="space-y-3">
          <label class="flex items-start gap-2 text-sm">
            <input v-model="form.consent" type="checkbox" class="mt-1" />
            <span>{{ t('register.student.consent') }}</span>
          </label>
        </div>
        <div v-else class="space-y-2 text-sm text-slate-700">
          <p>{{ form.student_first_name }} {{ form.student_last_name }}</p>
          <p>{{ form.guardian_name }} — {{ form.guardian_email }}</p>
          <p>{{ label(selectedLevel) || '—' }}</p>
          <p>{{ selectedSubjects.map(label).join(' · ') || '—' }}</p>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
          <button v-if="step > 1" type="button" class="rounded border px-4 py-2 text-sm" @click="prev">
            {{ t('admin.prev') }}
          </button>
          <button
            v-if="step < 6"
            type="button"
            class="rounded bg-[var(--rdp-forest)] px-4 py-2 text-sm text-white disabled:opacity-50"
            :disabled="!canNext()"
            @click="next"
          >
            {{ t('admin.next') }}
          </button>
          <button
            v-else
            type="button"
            class="rounded bg-[var(--rdp-forest)] px-4 py-2 text-sm text-white disabled:opacity-50"
            :disabled="!form.consent || sending"
            @click="submit"
          >
            {{ sending ? t('register.student.sending') : t('forms.send') }}
          </button>
        </div>
      </div>
    </section>
  </div>
</template>
