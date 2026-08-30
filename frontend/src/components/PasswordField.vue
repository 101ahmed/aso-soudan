<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

defineProps({
  modelValue: { type: String, default: '' },
  autocomplete: { type: String, default: 'current-password' },
  required: { type: Boolean, default: true },
  inputClass: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])
const { t } = useI18n()
const visible = ref(false)

const type = computed(() => (visible.value ? 'text' : 'password'))
const toggleLabel = computed(() => (visible.value ? t('auth.hidePassword') : t('auth.showPassword')))
</script>

<template>
  <div class="relative">
    <input
      :value="modelValue"
      :type="type"
      :autocomplete="autocomplete"
      :required="required"
      class="w-full rounded-md border border-slate-300 px-3 py-2 pe-[4.5rem] text-[var(--rdp-ink)] outline-none focus:border-[var(--rdp-forest)]"
      :class="inputClass"
      @input="emit('update:modelValue', $event.target.value)"
    />
    <button
      type="button"
      class="absolute inset-y-0 end-1 my-auto h-8 rounded px-2 text-xs font-semibold text-[var(--rdp-forest)] hover:bg-slate-100"
      :aria-label="toggleLabel"
      :aria-pressed="visible"
      @click="visible = !visible"
    >
      {{ toggleLabel }}
    </button>
  </div>
</template>
