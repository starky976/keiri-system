<!-- 固定資産 詳細・減価償却スケジュール -->
<template>
  <div class="max-w-2xl">
    <div class="flex items-center justify-between mb-4">
      <h1 class="page-title">固定資産 詳細</h1>
      <button @click="router.push(`/assets/${params.id}/edit`)" class="btn-secondary">編集</button>
    </div>
    <div v-if="asset" class="card mb-6 space-y-2">
      <div v-for="(v, k) in info" :key="k" class="flex gap-4 text-sm">
        <span class="w-32 text-gray-500 shrink-0">{{ k }}</span>
        <span class="font-medium">{{ v }}</span>
      </div>
    </div>
    <h2 class="font-semibold mb-2">減価償却スケジュール</h2>
    <table class="table">
      <thead><tr>
        <th class="th">年度</th><th class="th text-right">期首帳簿価額</th>
        <th class="th text-right">当年償却額</th><th class="th text-right">期末帳簿価額</th>
      </tr></thead>
      <tbody>
        <tr v-for="s in schedule" :key="s.year" class="hover:bg-gray-50">
          <td class="td">{{ s.year }}年</td>
          <td class="td text-right font-mono">{{ fmt(s.book_value_start) }}</td>
          <td class="td text-right font-mono">{{ fmt(s.depreciation) }}</td>
          <td class="td text-right font-mono">{{ fmt(s.book_value_end) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api/index.js'
import { router, useRoute } from '../../router/index.js'

const { params } = useRoute()
const asset      = ref(null)
const schedule   = ref([])
const fmt = (v) => Number(v).toLocaleString('ja-JP', { style: 'currency', currency: 'JPY' })

const info = computed(() => !asset.value ? {} : ({
  '資産番号':  asset.value.asset_number,
  '名称':      asset.value.name,
  '種別':      asset.value.category,
  '取得日':    asset.value.acquisition_date,
  '取得価額':  fmt(asset.value.acquisition_amount),
  '耐用年数':  asset.value.useful_life + '年',
  '償却方法':  asset.value.depreciation_method === 'straight_line' ? '定額法' : '定率法',
  '残存価額':  fmt(asset.value.residual_value),
  '廃棄日':    asset.value.disposal_date ?? '─',
}))

onMounted(async () => {
  const r = await api.get(`/fixed-assets/${params.value.id}/depreciation`)
  asset.value    = r.data.asset
  schedule.value = r.data.schedule
})
</script>
