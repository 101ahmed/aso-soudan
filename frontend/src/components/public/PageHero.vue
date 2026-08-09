<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <section class="border-b border-black/5 bg-[linear-gradient(135deg,#1f4d3a_0%,#2f6b4f_55%,#1a3328_100%)] px-5 py-14 text-white md:px-8">
    <div class="mx-auto max-w-6xl">
      <h1 class="font-[family-name:var(--font-display)] text-3xl font-bold md:text-5xl">
        {{ title }}
      </h1>
      <p v-if="subtitle" class="mt-4 max-w-3xl text-white/85">
        {{ subtitle }}
      </p>
    </div>
  </section>
</template>
