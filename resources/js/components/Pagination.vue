<template>
  <div v-if="data?.last_page > 1" class="flex items-center justify-between mt-4">
    <p class="text-sm text-gray-500">{{ data.from }}〜{{ data.to }}件 / 全{{ data.total }}件</p>
    <div class="flex gap-1">
      <button v-for="link in data.links" :key="link.label"
        v-html="link.label"
        :disabled="!link.url"
        @click="link.url && go(link.url)"
        class="px-3 py-1 text-sm rounded border transition"
        :class="link.active ? 'bg-blue-600 text-white border-blue-600' : link.url ? 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 cursor-pointer' : 'bg-white text-gray-300 border-gray-200 cursor-default'" />
    </div>
  </div>
</template>
<script setup>
const props = defineProps({ data: Object })
const emit = defineEmits(['page'])
function go(url) {
  const page = new URL(url).searchParams.get('page')
  emit('page', page)
}
</script>
