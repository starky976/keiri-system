<!-- 消費税管理ページ -->
<template>
  <div>
    <h1 class="page-title mb-6">消費税管理</h1>

    <!-- タブ -->
    <div class="flex gap-2 mb-6 border-b">
      <button v-for="t in tabs" :key="t.key" @click="tab = t.key"
        :class="['px-4 py-2 text-sm font-medium border-b-2 transition',
          tab === t.key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500']">
        {{ t.label }}
      </button>
    </div>

    <!-- 期間集計 -->
    <div v-if="tab === 'period'">
      <div class="flex gap-2 mb-4">
        <input v-model="from" type="date" class="input" />
        <span class="self-center">〜</span>
        <input v-model="to" type="date" class="input" />
        <button @click="loadPeriod" class="btn-primary">集計</button>
      </div>
      <div v-if="periodData">
        <div class="grid grid-cols-2 gap-4 mb-6">
          <div class="card text-center">
            <div class="text-sm text-gray-500 mb-1">消費税合計</div>
            <div class="text-2xl font-bold text-blue-600">{{ fmt(periodData.total_tax) }}</div>
          </div>
        </div>
        <table class="table">
          <thead><tr>
            <th class="th">税率</th><th class="th text-right">課税基準額</th><th class="th text-right">消費税額</th>
          </tr></thead>
          <tbody>
            <tr v-for="r in periodData.breakdown" :key="r.tax_rate" class="hover:bg-gray-50">
              <td class="td">{{ r.tax_rate }}%</td>
              <td class="td text-right font-mono">{{ fmt(r.taxable_base) }}</td>
              <td class="td text-right font-mono">{{ fmt(r.tax_amount) }}</td>
            </tr>
          </tbody>
        </table>
        <p v-if="periodData.note" class="text-xs text-amber-700 bg-amber-50 p-2 rounded mt-3">⚠ {{ periodData.note }}</p>
      </div>
    </div>

    <!-- 年次申告サマリー -->
    <div v-if="tab === 'summary'">
      <div class="flex gap-2 mb-4">
        <select v-model="summaryYear" class="input w-28">
          <option v-for="y in years" :key="y" :value="y">{{ y }}年</option>
        </select>
        <button @click="loadSummary" class="btn-primary">集計</button>
      </div>
      <div v-if="summaryData" class="space-y-4">
        <div class="grid grid-cols-3 gap-4">
          <div class="card text-center">
            <div class="text-sm text-gray-500 mb-1">課税売上高</div>
            <div class="text-xl font-bold text-green-700">{{ fmt(summaryData.taxable_sales) }}</div>
            <div class="text-sm text-gray-400">売上消費税 {{ fmt(summaryData.tax_on_sales) }}</div>
          </div>
          <div class="card text-center">
            <div class="text-sm text-gray-500 mb-1">課税仕入高</div>
            <div class="text-xl font-bold text-red-600">{{ fmt(summaryData.taxable_purchases) }}</div>
            <div class="text-sm text-gray-400">仕入消費税 {{ fmt(summaryData.tax_on_purchases) }}</div>
          </div>
          <div class="card text-center border-2 border-blue-200">
            <div class="text-sm text-gray-500 mb-1">納付消費税額（概算）</div>
            <div class="text-xl font-bold text-blue-600">{{ fmt(summaryData.tax_payable) }}</div>
          </div>
        </div>
        <p v-if="summaryData.note" class="text-xs text-amber-700 bg-amber-50 p-2 rounded">⚠ {{ summaryData.note }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../../api/index.js'

const tab         = ref('period')
const tabs        = [{ key: 'period', label: '期間別集計' }, { key: 'summary', label: '年次申告サマリー' }]
const from        = ref(new Date().getFullYear() + '-01-01')
const to          = ref(new Date().toISOString().slice(0, 10))
const summaryYear = ref(new Date().getFullYear())
const years       = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - 2 + i)
const periodData  = ref(null)
const summaryData = ref(null)
const fmt = (v) => Number(v).toLocaleString('ja-JP', { style: 'currency', currency: 'JPY' })

async function loadPeriod()  { periodData.value  = (await api.get('/tax',         { params: { from: from.value, to: to.value } })).data }
async function loadSummary() { summaryData.value = (await api.get('/tax-summary',  { params: { year: summaryYear.value } })).data }
</script>
