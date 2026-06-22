<template>
  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="cls">{{ lbl }}</span>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({ status: String })
const map = {
  draft:'bg-gray-100 text-gray-700', pending:'bg-yellow-100 text-yellow-800',
  approved:'bg-green-100 text-green-800', rejected:'bg-red-100 text-red-800',
  cancelled:'bg-gray-100 text-gray-500', invoiced:'bg-blue-100 text-blue-800',
  sent:'bg-blue-100 text-blue-800', paid:'bg-green-100 text-green-800',
  overdue:'bg-red-100 text-red-800', customer:'bg-indigo-100 text-indigo-800',
  vendor:'bg-orange-100 text-orange-800', both:'bg-purple-100 text-purple-800',
}
const labels = {
  draft:'下書き', pending:'申請中', approved:'承認済', rejected:'却下', cancelled:'キャンセル',
  invoiced:'請求済', sent:'送付済', paid:'入金済/支払済', overdue:'期限超過',
  customer:'得意先', vendor:'仕入先', both:'両方',
}
const cls = computed(() => map[props.status] ?? 'bg-gray-100 text-gray-600')
const lbl = computed(() => labels[props.status] ?? props.status)
</script>
