<script setup>
import { socialLinks } from '@/data/socialLinks'
</script>

<template>
  <div class="flex flex-wrap items-center gap-3">
    <a
      v-for="link in socialLinks"
      :key="link.platform"
      :href="link.url"
      :title="link.label"
      :aria-label="link.label"
      target="_blank"
      rel="noopener noreferrer"
      class="social-icon inline-flex h-11 w-11 items-center justify-center rounded-full text-white shadow-sm transition hover:scale-110 hover:opacity-90"
      :class="`social-icon--${link.platform}`"
      :style="link.platform === 'instagram' ? undefined : { backgroundColor: link.color }"
    >
      <svg v-if="link.platform === 'facebook'" viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true">
        <path d="M14.5 8H17V4h-2.7C11.6 4 10 5.8 10 8.6V11H7v4h3v9h4v-9h3.2L18 11h-4V8.8c0-.5.3-.8.5-.8z" />
      </svg>
      <svg v-else-if="link.platform === 'x'" viewBox="0 0 24 24" class="h-[18px] w-[18px] fill-current" aria-hidden="true">
        <path d="M17.6 3h3.2L13.9 10.7 22 21h-6.4l-5-6.6L5.4 21H2.2l7.5-8.6L2 3h6.5l4.5 6L17.6 3zm-1.1 16.2h1.8L7.6 4.7H5.7l10.8 14.5z" />
      </svg>
      <svg v-else-if="link.platform === 'linkedin'" viewBox="0 0 24 24" class="h-[18px] w-[18px] fill-current" aria-hidden="true">
        <path d="M6.5 9H3.8v11.2H6.5V9zM5.2 3.6a1.6 1.6 0 1 0 0 3.2 1.6 1.6 0 0 0 0-3.2zM20.2 12.6c0-3.1-1.7-4.6-3.9-4.6-1.8 0-2.6.9-3.1 1.6V8.2H10.5c0 2 0 12 0 12h2.7v-6.7c.2-.9.7-1.8 1.7-1.8s1.4.6 1.4 1.8V20.2h2.7v-7.6h.2z" />
      </svg>
      <svg v-else-if="link.platform === 'instagram'" viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true">
        <path d="M7.2 3h9.6A4.2 4.2 0 0 1 21 7.2v9.6A4.2 4.2 0 0 1 16.8 21H7.2A4.2 4.2 0 0 1 3 16.8V7.2A4.2 4.2 0 0 1 7.2 3zm9.6 1.8H7.2A2.4 2.4 0 0 0 4.8 7.2v9.6a2.4 2.4 0 0 0 2.4 2.4h9.6a2.4 2.4 0 0 0 2.4-2.4V7.2a2.4 2.4 0 0 0-2.4-2.4zM12 8.1A3.9 3.9 0 1 1 8.1 12 3.9 3.9 0 0 1 12 8.1zm0 1.8A2.1 2.1 0 1 0 14.1 12 2.1 2.1 0 0 0 12 9.9zm4.7-3.1a1 1 0 1 1-1 1 1 1 0 0 1 1-1z" />
      </svg>
      <svg v-else-if="link.platform === 'whatsapp'" viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true">
        <path d="M12.04 3a8.9 8.9 0 0 0-7.6 13.5L3 21.1l4.7-1.2A8.9 8.9 0 1 0 12.04 3zm5.2 12.7c-.2.6-1.2 1.1-1.7 1.2-.4.1-.9.2-1.5 0-.3 0-.7-.1-1.2-.3a13 13 0 0 1-5-4.3 6 6 0 0 1-1.2-3.1c0-.9.5-1.8 1-2.1.3-.2.6-.2.8-.2h.6c.2 0 .4 0 .6.5l.8 2c.1.2 0 .4 0 .5l-.4.5c-.1.1-.2.3 0 .5a8 8 0 0 0 1.4 1.7 8.4 8.4 0 0 0 2 1.3c.2.1.4.1.5 0l.5-.6c.2-.2.4-.1.6 0l1.7.9c.2.1.3.2.4.3 0 .2 0 .6-.2 1.2z" />
      </svg>
    </a>
  </div>
</template>

<style scoped>
.social-icon--instagram {
  background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fd5949 45%, #d6249f 60%, #285aeb 90%);
}
</style>
