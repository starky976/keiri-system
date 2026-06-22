<template>
  <div class="bg-white rounded-lg border shadow-sm p-5 flex justify-between items-center">
    <div>
      <p class="text-sm text-gray-500">{{ label }}</p>
      <p class="text-2xl font-bold mt-1" :class="colorCls">{{ value }}</p>
    </div>
    <span class="text-3xl">{{ icon }}</span>
  </div>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({ label:String, value:String, icon:String, color:{type:String,default:'blue'} })
const colorCls = computed(() => ({'blue':'text-blue-700','green':'text-green-700','red':'text-red-600','yellow':'text-yellow-700'}[props.color]??'text-gray-800'))
</script>
