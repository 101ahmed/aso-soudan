<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { pickName } from '@/utils/localized'
import { fetchPresidentSecretariats, sendPresidentDirective } from '@/services/president'

const open = defineModel({ type: Boolean, default: false })
const emit = defineEmits(['sent'])

const { t, locale } = useI18n()
const secretariats = ref([])
const saving = ref(false)
const error = ref('')
const success = ref('')

const form = reactive({
  department_id: '',
  assigned_to_user_id: '',
  title: '',
  body: '',
  classification: 'info',
})

const selected = computed(() =>
  secretariats.value.find((item) => String(item.id) === String(form.department_id)),
)

const managerLabel = computed(() => {
  if (!selected.value) return ''
  if (selected.value.manager?.name) return selected.value.manager.name
  return pickName(selected.value.officer, locale.value) || t('presidentAdmin.officerFallback')
})

function reset() {
  form.department_id = ''
  form.assigned_to_user_id = ''
  form.title = ''
  form.body = ''
  form.classification = 'info'
  error.value = ''
  success.value = ''
}

watch(open, async (value) => {
  if (!value) return
  error.value = ''
  success.value = ''
  try {
    secretariats.value = await fetchPresidentSecretariats()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
})

watch(() => form.department_id, (id) => {
  const dept = secretariats.value.find((item) => String(item.id) === String(id))
  form.assigned_to_user_id = dept?.manager?.id || dept?.managers?.[0]?.id || ''
})

async function submit() {
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    await sendPresidentDirective({
      department_id: Number(form.department_id),
      assigned_to_user_id: form.assigned_to_user_id ? Number(form.assigned_to_user_id) : null,
      title: form.title || null,
      body: form.body,
      classification: form.classification,
    })
    success.value = t('presidentAdmin.directiveSent')
    emit('sent')
    reset()
    open.value = false
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
    <form class="w-full max-w-lg space-y-3 rounded-xl border bg-white p-5 shadow-xl" @submit.prevent="submit">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('presidentAdmin.sendDirective') }}</h2>
          <p class="mt-1 text-sm text-slate-600">{{ t('presidentAdmin.directiveHint') }}</p>
        </div>
        <button type="button" class="text-sm text-slate-500 hover:text-slate-800" @click="open = false">
          {{ t('forms.cancel') }}
        </button>
      </div>

      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>

      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('presidentAdmin.targetSecretariat') }}</span>
        <select v-model="form.department_id" required class="w-full rounded border px-3 py-2 text-sm">
          <option value="" disabled>{{ t('presidentAdmin.chooseSecretariat') }}</option>
          <option v-for="dept in secretariats" :key="dept.id" :value="dept.id">
            {{ pickName(dept, locale) }}
          </option>
        </select>
      </label>

      <p v-if="selected" class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
        {{ t('presidentAdmin.goesToManager') }}:
        <strong>{{ managerLabel }}</strong>
        <span v-if="selected.manager?.email" class="text-slate-500"> · {{ selected.manager.email }}</span>
      </p>

      <label v-if="selected?.managers?.length > 1" class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('presidentAdmin.chooseManager') }}</span>
        <select v-model="form.assigned_to_user_id" class="w-full rounded border px-3 py-2 text-sm">
          <option v-for="manager in selected.managers" :key="manager.id" :value="manager.id">
            {{ manager.name }} {{ manager.email ? `(${manager.email})` : '' }}
          </option>
        </select>
      </label>

      <input v-model="form.title" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.directiveTitle')" />

      <select v-model="form.classification" class="w-full rounded border px-3 py-2 text-sm">
        <option value="urgent">{{ t('presidentAdmin.classifications.urgent') }}</option>
        <option value="follow_up">{{ t('presidentAdmin.classifications.follow_up') }}</option>
        <option value="info">{{ t('presidentAdmin.classifications.info') }}</option>
      </select>

      <textarea
        v-model="form.body"
        required
        rows="6"
        class="w-full rounded border px-3 py-2 text-sm"
        :placeholder="t('presidentAdmin.directiveBody')"
      />

      <div class="flex justify-end gap-2">
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="open = false">{{ t('forms.cancel') }}</button>
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="saving">
          {{ t('presidentAdmin.send') }}
        </button>
      </div>
    </form>
  </div>
</template>
