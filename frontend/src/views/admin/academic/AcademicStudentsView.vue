<script setup>
import { computed, onMounted, reactive, ref, shallowRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createStudent,
  deleteStudent,
  downloadStudentDossierPdf,
  fetchStudentCatalog,
  fetchStudents,
  updateStudent,
} from '@/services/academic'
import { pickName } from '@/utils/localized'
import { prepareUploadImage } from '@/utils/prepareUploadImage'

const { t, locale } = useI18n()
const auth = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const items = ref([])
const meta = ref(null)
const catalog = ref({ academic_years: [], stages: [], subjects: [], level_counts: {} })
const editingId = ref(null)
const downloadingId = ref(null)
const photoFile = shallowRef(null)
const photoPreview = ref(null)
const photoInput = ref(null)
const filters = reactive({ search: '', status: '', level_id: '', page: 1 })
const form = reactive({
  first_name: '', last_name: '', birth_date: '', gender: '', academic_year_id: '',
  education_stage_id: '', level_id: '', status: 'active', notes: '', subject_ids: [],
  remove_photo: false,
})

const isTeacher = computed(() => auth.user?.roles?.some((r) => r.code === 'TEACHER'))
const canView = computed(() => auth.hasPermission('student.view') || isTeacher.value)
const canCreate = computed(() => auth.hasPermission('student.create') || isTeacher.value)
const canUpdate = computed(() => auth.hasPermission('student.update') || isTeacher.value)
const canDelete = computed(() => auth.hasPermission('student.delete') || auth.hasPermission('student.update') || isTeacher.value)
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))
const levels = computed(() => {
  const stage = (catalog.value.stages || []).find((item) => String(item.id) === String(form.education_stage_id))
  return stage?.levels || []
})
const catalogLevels = computed(() => (catalog.value.stages || []).flatMap((stage) => stage.levels || []))
const groupedItems = computed(() => {
  if (filters.level_id) return null
  return catalogLevels.value.map((level) => ({
    level,
    items: items.value.filter((item) => String(item.level_id) === String(level.id)),
  }))
})
const unassignedItems = computed(() => items.value.filter((item) => !item.level_id))
const formAge = computed(() => yearsFromBirthDate(form.birth_date))

function levelCount(id) {
  const counts = catalog.value.level_counts || {}
  return Number(counts[id] ?? counts[String(id)] ?? 0)
}

function selectLevel(id) {
  filters.level_id = id
  filters.page = 1
  load()
}

function label(item) {
  if (!item) return ''
  return pickName(item, locale.value)
}

function yearsFromBirthDate(value) {
  if (!value) return null
  const birth = new Date(`${value}T00:00:00`)
  if (Number.isNaN(birth.getTime())) return null
  const now = new Date()
  let years = now.getFullYear() - birth.getFullYear()
  const monthDiff = now.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birth.getDate())) years -= 1
  return years >= 0 ? years : null
}

function ageLabel(item) {
  const years = item?.age ?? yearsFromBirthDate(item?.birth_date)
  if (years == null) return '—'
  return `${years} ${t('academicStudents.years')}`
}

function resetPhoto() {
  photoFile.value = null
  photoPreview.value = null
  form.remove_photo = false
  if (photoInput.value) photoInput.value.value = ''
}

function resetForm() {
  editingId.value = null
  const currentYear = (catalog.value.academic_years || []).find((year) => year.is_current)
  const defaultStage = catalog.value.stages?.[0]
  Object.assign(form, {
    first_name: '', last_name: '', birth_date: '', gender: '',
    academic_year_id: currentYear?.id || catalog.value.academic_years?.[0]?.id || '',
    education_stage_id: defaultStage?.id || '', level_id: '', status: 'active', notes: '', subject_ids: [],
    remove_photo: false,
  })
  resetPhoto()
}

function toggleSubject(id) {
  const value = Number(id)
  if (form.subject_ids.includes(value)) form.subject_ids = form.subject_ids.filter((item) => item !== value)
  else form.subject_ids.push(value)
}

async function load() {
  if (!canView.value) return
  loading.value = true
  error.value = ''
  try {
    const response = await fetchStudents({
      page: filters.page,
      search: filters.search || undefined,
      status: filters.status || undefined,
      level_id: filters.level_id || undefined,
      per_page: 100,
    })
    items.value = response.data || []
    meta.value = response.meta || null
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

function edit(item) {
  editingId.value = item.id
  Object.assign(form, {
    first_name: item.first_name || '', last_name: item.last_name || '', birth_date: item.birth_date || '',
    gender: item.gender || '', academic_year_id: item.academic_year_id || '',
    education_stage_id: item.education_stage_id || '', level_id: item.level_id || '',
    status: item.status || 'active', notes: item.notes || '',
    subject_ids: (item.subjects || []).map((subject) => subject.id),
    remove_photo: false,
  })
  photoFile.value = null
  photoPreview.value = item.photo_url || null
  if (photoInput.value) photoInput.value.value = ''
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

function clearPhoto() {
  photoFile.value = null
  form.remove_photo = true
  photoPreview.value = null
  if (photoInput.value) photoInput.value.value = ''
}

function payload() {
  return {
    first_name: form.first_name,
    last_name: form.last_name,
    birth_date: form.birth_date || null,
    gender: form.gender || null,
    academic_year_id: form.academic_year_id || null,
    education_stage_id: form.education_stage_id || null,
    level_id: form.level_id || null,
    status: form.status,
    notes: form.notes || '',
    subject_ids: form.subject_ids,
    photo: photoFile.value || undefined,
    remove_photo: form.remove_photo || undefined,
  }
}

async function save() {
  if (!canManage.value) return
  saving.value = true
  error.value = ''
  try {
    if (editingId.value) await updateStudent(editingId.value, payload())
    else await createStudent(payload())
    resetForm()
    try { catalog.value = await fetchStudentCatalog() } catch { /* keep current catalog */ }
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  if (!canDelete.value) return
  if (!confirm(t('academicStudents.confirmDelete', { name: item.full_name }))) return
  try {
    await deleteStudent(item.id)
    if (editingId.value === item.id) resetForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function downloadPdf(item) {
  if (!item?.id) return
  downloadingId.value = item.id
  error.value = ''
  try {
    const { blob, contentType } = await downloadStudentDossierPdf(item.id, { locale: locale.value })
    if (blob.type.includes('json') || String(contentType).includes('json')) {
      const payloadJson = JSON.parse(await blob.text())
      throw new Error(payloadJson.message || t('academicStudents.downloadFailed'))
    }
    const url = URL.createObjectURL(blob)
    const isPdf = String(contentType).includes('pdf') || blob.type.includes('pdf')
    const a = document.createElement('a')
    a.href = url
    a.download = `dossier-${item.id}.${isPdf ? 'pdf' : 'html'}`
    document.body.appendChild(a)
    a.click()
    a.remove()
    setTimeout(() => URL.revokeObjectURL(url), 1500)
  } catch (e) {
    const data = e.response?.data
    if (data instanceof Blob) {
      try {
        const payloadJson = JSON.parse(await data.text())
        error.value = payloadJson.message || t('academicStudents.downloadFailed')
      } catch {
        error.value = t('academicStudents.downloadFailed')
      }
    } else {
      error.value = e.message || e.response?.data?.message || t('academicStudents.downloadFailed')
    }
  } finally {
    downloadingId.value = null
  }
}

watch(() => form.education_stage_id, (next, prev) => {
  if (prev && next !== prev) {
    const stillValid = levels.value.some((level) => String(level.id) === String(form.level_id))
    if (!stillValid) form.level_id = ''
  }
})

onMounted(async () => {
  try { catalog.value = await fetchStudentCatalog() } catch { catalog.value = { academic_years: [], stages: [], subjects: [], level_counts: {} } }
  resetForm()
  await load()
})
</script>

<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('academicStudents.title') }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t('academicStudents.subtitle') }}</p>
    </div>
    <p v-if="!canView" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ t('academicStudents.forbidden') }}</p>
    <template v-else>
      <div class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-3">
        <input v-model="filters.search" type="search" :placeholder="t('academicStudents.search')" class="rounded-md border px-3 py-2 text-sm" @keyup.enter="filters.page = 1; load()" />
        <select v-model="filters.status" class="rounded-md border px-3 py-2 text-sm">
          <option value="">{{ t('academicStudents.allStatuses') }}</option>
          <option value="active">{{ t('academicStudents.statuses.active') }}</option>
          <option value="pending">{{ t('academicStudents.statuses.pending') }}</option>
          <option value="inactive">{{ t('academicStudents.statuses.inactive') }}</option>
        </select>
        <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="filters.page = 1; load()">{{ t('academicStudents.filter') }}</button>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-full px-3 py-1.5 text-sm"
          :class="!filters.level_id ? 'bg-teal-800 text-white' : 'border bg-white text-slate-700'"
          @click="selectLevel('')"
        >
          {{ t('academicStudents.allLevels') }}
        </button>
        <button
          v-for="level in catalogLevels"
          :key="level.id"
          type="button"
          class="rounded-full px-3 py-1.5 text-sm"
          :class="String(filters.level_id) === String(level.id) ? 'bg-teal-800 text-white' : 'border bg-white text-slate-700'"
          @click="selectLevel(String(level.id))"
        >
          {{ label(level) }}
          <span class="opacity-80">({{ levelCount(level.id) }})</span>
        </button>
      </div>
      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-4">
          <template v-if="groupedItems">
            <div
              v-for="group in groupedItems"
              :key="group.level.id"
              class="overflow-x-auto rounded-xl border bg-white"
            >
              <div class="flex items-center justify-between border-b px-4 py-3">
                <h3 class="font-semibold text-[var(--rdp-forest)]">{{ label(group.level) }}</h3>
                <p class="text-xs text-slate-500">{{ group.items.length }} {{ t('academicStudents.studentsCount') }}</p>
              </div>
              <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-start text-xs text-slate-500">
                  <tr>
                    <th class="px-4 py-3 font-medium">{{ t('academicStudents.name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ t('academicStudents.age') }}</th>
                    <th class="px-4 py-3 font-medium">{{ t('academicStudents.subjects') }}</th>
                    <th class="px-4 py-3 font-medium">{{ t('academicStudents.status') }}</th>
                    <th class="px-4 py-3"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!loading && !group.items.length">
                    <td colspan="5" class="px-4 py-6 text-center text-slate-500">{{ t('academicStudents.emptyLevel') }}</td>
                  </tr>
                  <tr v-for="item in group.items" :key="item.id" class="border-t">
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-2">
                        <img v-if="item.photo_url" :src="item.photo_url" alt="" class="h-9 w-9 rounded-full object-cover object-top" />
                        <span class="font-medium">{{ item.full_name }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-3">{{ ageLabel(item) }}</td>
                    <td class="px-4 py-3">{{ (item.subjects || []).map(label).join(' · ') || '—' }}</td>
                    <td class="px-4 py-3">{{ t(`academicStudents.statuses.${item.status}`) }}</td>
                    <td class="px-4 py-3 flex flex-wrap gap-2">
                      <button type="button" class="text-teal-800 hover:underline" :disabled="downloadingId === item.id" @click="downloadPdf(item)">
                        {{ downloadingId === item.id ? t('academicStudents.downloading') : t('academicStudents.downloadPdf') }}
                      </button>
                      <button v-if="canUpdate" type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                      <button v-if="canDelete" type="button" class="text-rose-700 hover:underline" @click="remove(item)">{{ t('forms.delete') }}</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-if="unassignedItems.length" class="overflow-x-auto rounded-xl border bg-white">
              <div class="border-b px-4 py-3">
                <h3 class="font-semibold text-slate-700">{{ t('academicStudents.unassigned') }}</h3>
              </div>
              <table class="min-w-full text-sm">
                <tbody>
                  <tr v-for="item in unassignedItems" :key="item.id" class="border-t">
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-2">
                        <img v-if="item.photo_url" :src="item.photo_url" alt="" class="h-9 w-9 rounded-full object-cover object-top" />
                        <span class="font-medium">{{ item.full_name }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-3">{{ ageLabel(item) }}</td>
                    <td class="px-4 py-3">{{ (item.subjects || []).map(label).join(' · ') || '—' }}</td>
                    <td class="px-4 py-3 flex flex-wrap gap-2">
                      <button type="button" class="text-teal-800 hover:underline" :disabled="downloadingId === item.id" @click="downloadPdf(item)">
                        {{ downloadingId === item.id ? t('academicStudents.downloading') : t('academicStudents.downloadPdf') }}
                      </button>
                      <button v-if="canUpdate" type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
          <div v-else class="overflow-x-auto rounded-xl border bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-start text-xs text-slate-500">
              <tr>
                <th class="px-4 py-3 font-medium">{{ t('academicStudents.name') }}</th>
                <th class="px-4 py-3 font-medium">{{ t('academicStudents.age') }}</th>
                <th class="px-4 py-3 font-medium">{{ t('academicStudents.level') }}</th>
                <th class="px-4 py-3 font-medium">{{ t('academicStudents.subjects') }}</th>
                <th class="px-4 py-3 font-medium">{{ t('academicStudents.status') }}</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!loading && !items.length"><td colspan="6" class="px-4 py-6 text-center text-slate-500">{{ t('academicStudents.empty') }}</td></tr>
              <tr v-for="item in items" :key="item.id" class="border-t">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <img v-if="item.photo_url" :src="item.photo_url" alt="" class="h-9 w-9 rounded-full object-cover object-top" />
                    <span class="font-medium">{{ item.full_name }}</span>
                  </div>
                </td>
                <td class="px-4 py-3">{{ ageLabel(item) }}</td>
                <td class="px-4 py-3">{{ label(item.level) || '—' }}</td>
                <td class="px-4 py-3">{{ (item.subjects || []).map(label).join(' · ') || '—' }}</td>
                <td class="px-4 py-3">{{ t(`academicStudents.statuses.${item.status}`) }}</td>
                <td class="px-4 py-3 flex flex-wrap gap-2">
                  <button type="button" class="text-teal-800 hover:underline" :disabled="downloadingId === item.id" @click="downloadPdf(item)">
                    {{ downloadingId === item.id ? t('academicStudents.downloading') : t('academicStudents.downloadPdf') }}
                  </button>
                  <button v-if="canUpdate" type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                  <button v-if="canDelete" type="button" class="text-rose-700 hover:underline" @click="remove(item)">{{ t('forms.delete') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
          </div>
        </div>
        <form v-if="canCreate || canUpdate" class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
          <h3 class="font-semibold">{{ editingId ? t('academicStudents.editStudent') : t('academicStudents.newStudent') }}</h3>
          <div class="flex flex-wrap items-center gap-3">
            <img v-if="photoPreview" :src="photoPreview" alt="" class="h-20 w-20 rounded-xl object-cover object-top ring-2 ring-teal-800/20" />
            <div v-else class="flex h-20 w-20 items-center justify-center rounded-xl bg-teal-800 text-lg font-bold text-white">
              {{ (form.first_name || '?').slice(0, 1) }}
            </div>
            <div class="space-y-1 text-sm">
              <p class="text-xs text-slate-500">{{ t('academicStudents.photo') }}</p>
              <input ref="photoInput" type="file" accept="image/*" class="w-full text-xs" @change="onPhotoPick" />
              <button v-if="photoPreview" type="button" class="text-xs text-rose-700 hover:underline" @click="clearPhoto">
                {{ t('academicStudents.removePhoto') }}
              </button>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <input v-model="form.first_name" required class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.firstName')" />
            <input v-model="form.last_name" required class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.lastName')" />
          </div>
          <label class="block text-xs text-slate-500">
            {{ t('academicStudents.birthDate') }}
            <input v-model="form.birth_date" type="date" class="mt-1 w-full rounded border px-3 py-2 text-sm text-slate-800" />
          </label>
          <p v-if="formAge != null" class="text-xs text-slate-500">{{ t('academicStudents.age') }}: {{ formAge }} {{ t('academicStudents.years') }}</p>
          <select v-model="form.gender" class="w-full rounded border px-3 py-2 text-sm">
            <option value="">{{ t('academicStudents.gender') }}</option>
            <option value="male">{{ t('academicStudents.genders.male') }}</option>
            <option value="female">{{ t('academicStudents.genders.female') }}</option>
          </select>
          <select v-model="form.academic_year_id" class="w-full rounded border px-3 py-2 text-sm">
            <option value="">{{ t('academicStudents.year') }}</option>
            <option v-for="year in catalog.academic_years" :key="year.id" :value="year.id">{{ year.name }}</option>
          </select>
          <select v-model="form.education_stage_id" class="w-full rounded border px-3 py-2 text-sm">
            <option value="">{{ t('academicStudents.stage') }}</option>
            <option v-for="stage in catalog.stages" :key="stage.id" :value="stage.id">{{ label(stage) }}</option>
          </select>
          <select v-model="form.level_id" class="w-full rounded border px-3 py-2 text-sm">
            <option value="">{{ t('academicStudents.level') }}</option>
            <option v-for="level in levels" :key="level.id" :value="level.id">{{ label(level) }}</option>
          </select>
          <fieldset class="rounded-lg border p-3">
            <legend class="px-1 text-xs">{{ t('academicStudents.subjects') }}</legend>
            <label v-for="subject in catalog.subjects" :key="subject.id" class="flex items-center gap-2 text-sm">
              <input type="checkbox" :checked="form.subject_ids.includes(subject.id)" @change="toggleSubject(subject.id)" />
              {{ label(subject) }}
            </label>
          </fieldset>
          <label class="block text-xs text-slate-500">
            {{ t('academicStudents.counselorNotes') }}
            <textarea v-model="form.notes" rows="4" class="mt-1 w-full rounded border px-3 py-2 text-sm text-slate-800"></textarea>
          </label>
          <div class="flex flex-wrap gap-2">
            <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white" :disabled="saving">{{ t('forms.save') }}</button>
            <button
              v-if="editingId"
              type="button"
              class="rounded border border-teal-800 px-4 py-2 text-sm font-semibold text-teal-800 disabled:opacity-60"
              :disabled="downloadingId === editingId"
              @click="downloadPdf({ id: editingId, full_name: `${form.first_name} ${form.last_name}`.trim() })"
            >
              {{ downloadingId === editingId ? t('academicStudents.downloading') : t('academicStudents.downloadPdf') }}
            </button>
            <button v-if="editingId" type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>
