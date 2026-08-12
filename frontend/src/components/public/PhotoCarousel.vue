<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  slides: { type: Array, default: () => [] },
  interval: { type: Number, default: 5000 },
  perViewDesktop: { type: Number, default: 3 },
  perViewTablet: { type: Number, default: 2 },
  perViewMobile: { type: Number, default: 1 },
  aspect: { type: String, default: 'aspect-[4/3]' },
  showCaptions: { type: Boolean, default: true },
})

const { t, locale } = useI18n()
const index = ref(0)
const paused = ref(false)
const lightboxOpen = ref(false)
const lightboxIndex = ref(0)
const perView = ref(props.perViewDesktop)
const touchStartX = ref(0)
const touchDelta = ref(0)

const isRtl = computed(() => locale.value === 'ar')
const total = computed(() => props.slides.length)
const maxIndex = computed(() => Math.max(0, total.value - perView.value))
const pageCount = computed(() => Math.max(1, maxIndex.value + 1))
const slideFlex = computed(() => `${100 / perView.value}%`)
const currentLightbox = computed(() => props.slides[lightboxIndex.value] || null)

const trackStyle = computed(() => {
  const step = 100 / perView.value
  // Track is always LTR for reliable math; arrows/UI use logical start/end
  const base = -index.value * step
  return {
    transform: `translate3d(calc(${base}% + ${touchDelta.value}px), 0, 0)`,
    transition: touchDelta.value ? 'none' : 'transform 520ms cubic-bezier(0.22, 1, 0.36, 1)',
  }
})

function updatePerView() {
  const w = window.innerWidth
  if (w < 640) perView.value = props.perViewMobile
  else if (w < 1024) perView.value = props.perViewTablet
  else perView.value = props.perViewDesktop
  index.value = Math.min(index.value, maxIndex.value)
}

function next() {
  if (!total.value) return
  index.value = index.value >= maxIndex.value ? 0 : index.value + 1
}

function prev() {
  if (!total.value) return
  index.value = index.value <= 0 ? maxIndex.value : index.value - 1
}

function goTo(i) {
  index.value = Math.min(Math.max(0, i), maxIndex.value)
}

function openLightbox(i) {
  lightboxIndex.value = i
  lightboxOpen.value = true
  paused.value = true
}

function closeLightbox() {
  lightboxOpen.value = false
  paused.value = false
}

function lightboxNext() {
  if (!total.value) return
  lightboxIndex.value = (lightboxIndex.value + 1) % total.value
}

function lightboxPrev() {
  if (!total.value) return
  lightboxIndex.value = (lightboxIndex.value - 1 + total.value) % total.value
}

function onKey(e) {
  if (!lightboxOpen.value) return
  if (e.key === 'Escape') closeLightbox()
  if (e.key === 'ArrowRight') (isRtl.value ? lightboxPrev : lightboxNext)()
  if (e.key === 'ArrowLeft') (isRtl.value ? lightboxNext : lightboxPrev)()
}

function onTouchStart(e) {
  touchStartX.value = e.touches[0].clientX
  touchDelta.value = 0
  paused.value = true
}

function onTouchMove(e) {
  touchDelta.value = e.touches[0].clientX - touchStartX.value
}

function onTouchEnd() {
  const threshold = 48
  if (touchDelta.value > threshold) prev()
  else if (touchDelta.value < -threshold) next()
  touchDelta.value = 0
  if (!lightboxOpen.value) paused.value = false
}

let timer = null
function startTimer() {
  stopTimer()
  if (!props.interval || total.value <= perView.value) return
  timer = window.setInterval(() => {
    if (!paused.value && !lightboxOpen.value) next()
  }, props.interval)
}
function stopTimer() {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}

watch([() => props.slides.length, perView, () => props.interval], startTimer)
watch(lightboxOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => {
  updatePerView()
  window.addEventListener('resize', updatePerView)
  window.addEventListener('keydown', onKey)
  startTimer()
})

onBeforeUnmount(() => {
  stopTimer()
  window.removeEventListener('resize', updatePerView)
  window.removeEventListener('keydown', onKey)
  document.body.style.overflow = ''
})
</script>

<template>
  <div
    v-if="slides.length"
    class="photo-carousel relative"
    @mouseenter="paused = true"
    @mouseleave="paused = lightboxOpen"
  >
    <div class="relative overflow-hidden" dir="ltr">
      <div
        class="flex w-full will-change-transform"
        :style="trackStyle"
        @touchstart.passive="onTouchStart"
        @touchmove.passive="onTouchMove"
        @touchend="onTouchEnd"
      >
        <button
          v-for="(slide, i) in slides"
          :key="`${slide.src}-${i}`"
          type="button"
          class="group box-border shrink-0 px-1.5 text-start sm:px-2"
          :style="{ flex: `0 0 ${slideFlex}`, maxWidth: slideFlex }"
          @click="openLightbox(i)"
        >
          <div :class="['overflow-hidden rounded-2xl bg-slate-100 shadow-sm', aspect]">
            <img
              :src="slide.src"
              :alt="slide.title || slide.caption || ''"
              loading="lazy"
              decoding="async"
              class="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-105"
            />
          </div>
          <p
            v-if="showCaptions && (slide.title || slide.caption)"
            class="mt-2 truncate px-0.5 text-sm font-medium text-[var(--rdp-forest)]"
          >
            {{ slide.caption || slide.title }}
          </p>
        </button>
      </div>

      <button
        v-if="maxIndex > 0"
        type="button"
        class="absolute start-1 top-[42%] z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-xl leading-none text-[var(--rdp-forest)] shadow ring-1 ring-black/5 hover:bg-white sm:start-2"
        :aria-label="t('carousel.prev')"
        @click.stop="prev"
      >
        ‹
      </button>
      <button
        v-if="maxIndex > 0"
        type="button"
        class="absolute end-1 top-[42%] z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-xl leading-none text-[var(--rdp-forest)] shadow ring-1 ring-black/5 hover:bg-white sm:end-2"
        :aria-label="t('carousel.next')"
        @click.stop="next"
      >
        ›
      </button>
    </div>

    <div v-if="pageCount > 1" class="mt-4 flex justify-center gap-2">
      <button
        v-for="n in pageCount"
        :key="n"
        type="button"
        class="h-2.5 rounded-full transition-all"
        :class="n - 1 === index ? 'w-6 bg-[var(--rdp-forest)]' : 'w-2.5 bg-slate-300 hover:bg-slate-400'"
        :aria-label="t('carousel.goto', { n })"
        @click="goTo(n - 1)"
      />
    </div>

    <Teleport to="body">
      <div
        v-if="lightboxOpen && currentLightbox"
        class="fixed inset-0 z-[80] flex flex-col bg-black/90"
        role="dialog"
        aria-modal="true"
        @click.self="closeLightbox"
      >
        <button
          type="button"
          class="absolute end-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-2xl leading-none text-white hover:bg-white/25"
          :aria-label="t('carousel.close')"
          @click="closeLightbox"
        >
          ×
        </button>

        <div class="relative flex min-h-0 flex-1 items-center justify-center px-4 py-16 sm:px-14">
          <button
            type="button"
            class="absolute start-2 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl text-white hover:bg-white/25 md:start-6"
            :aria-label="t('carousel.prev')"
            @click="lightboxPrev"
          >
            ‹
          </button>
          <img
            :src="currentLightbox.src"
            :alt="currentLightbox.title || ''"
            class="max-h-[min(78vh,900px)] max-w-full rounded-xl object-contain shadow-2xl"
          />
          <button
            type="button"
            class="absolute end-2 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl text-white hover:bg-white/25 md:end-6"
            :aria-label="t('carousel.next')"
            @click="lightboxNext"
          >
            ›
          </button>
        </div>

        <div class="shrink-0 px-6 pb-8 text-center text-white">
          <p v-if="currentLightbox.title || currentLightbox.caption" class="text-base font-medium md:text-lg">
            {{ currentLightbox.caption || currentLightbox.title }}
          </p>
          <p class="mt-1 text-sm text-white/60">{{ lightboxIndex + 1 }} / {{ total }}</p>
        </div>
      </div>
    </Teleport>
  </div>
</template>
