<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  image: { type: String, default: '' },
  tone: { type: String, default: 'forest' },
})

const tones = {
  forest: 'bg-[linear-gradient(135deg,#1f4d3a_0%,#2f6b4f_55%,#1a3328_100%)]',
  blue: 'bg-[linear-gradient(135deg,#1d4ed8_0%,#3b82f6_48%,#1e3a8a_100%)]',
}

const overlays = {
  forest: 'bg-[linear-gradient(115deg,rgba(18,40,28,0.72),rgba(18,40,28,0.35))]',
  blue: 'bg-[linear-gradient(115deg,rgba(29,78,216,0.55),rgba(30,58,138,0.28))]',
}
</script>

<template>
  <section
    v-if="image"
    class="relative min-h-[48vh] overflow-hidden bg-black text-white"
  >
    <img :src="image" alt="" class="absolute inset-0 h-full w-full object-contain object-center" />
    <div class="absolute inset-0" :class="overlays[tone] || overlays.forest" />
    <div class="relative z-10 mx-auto flex min-h-[48vh] max-w-6xl flex-col justify-end px-5 py-12 md:px-8">
      <h1 class="font-[family-name:var(--font-display)] text-3xl font-bold md:text-5xl">
        {{ title }}
      </h1>
      <p v-if="subtitle" class="mt-4 max-w-3xl text-white/85">
        {{ subtitle }}
      </p>
    </div>
  </section>
  <section
    v-else
    class="border-b border-black/5 px-5 py-10 text-white md:px-8 md:py-14"
    :class="tones[tone] || tones.forest"
  >
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
