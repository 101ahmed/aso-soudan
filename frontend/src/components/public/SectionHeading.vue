<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <div class="mb-8 max-w-3xl">
    <h2 class="font-[family-name:var(--font-display)] text-3xl font-bold text-[var(--rdp-forest)] md:text-4xl">
      {{ title }}
    </h2>
    <p v-if="subtitle" class="mt-3 text-base text-slate-600 md:text-lg">
      {{ subtitle }}
    </p>
  </div>
</template>
