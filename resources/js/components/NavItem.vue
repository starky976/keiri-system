<template>
  <a :href="href"
     class="flex items-center px-4 py-2 mx-2 rounded-md text-sm transition-colors"
     :class="isActive ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'">
    <span class="mr-3">{{ icon }}</span>
    <slot />
  </a>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '../router/index.js'

const props = defineProps({ href: String, icon: String })

const isActive = computed(() => {
  const target = props.href.replace(/^#/, '')
  const current = router.currentHash.value
  if (target === '/' || target === '/dashboard') return current === '/' || current === '/dashboard'
  return current.startsWith(target)
})
</script>
