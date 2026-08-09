<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'

const route = useRoute()

const isAdminShell = computed(
  () => route.path.startsWith('/admin') || route.name === 'login',
)
</script>

<template>
  <RouterView />
</template>
