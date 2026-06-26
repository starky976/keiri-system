<!--
  損益計算書（P/L）ページ

  指定期間の収益・費用を勘定科目ごとに集計し、
  当期純利益（収益合計 - 費用合計）を表示する。
  デフォルトは当年1月1日〜今日の範囲。
-->
<template>
  <div>
    <!-- 期間指定フォーム -->
    <div class="bg-white rounded-lg border shadow-sm p-4 mb-6 flex gap-3 items-center">
      <label class="text-sm font-medium text-gray-700">期間</label>
      <input v-model="from" type="date" class="input w-40" />
      <span class="text-gray-400">〜</span>
      <input v-model="to" type="date" class="input w-40" />
      <button @click="load" class="btn-primary">表示</button>
    </div>

    <!-- 収益・費用の2列レイアウト -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- 収益の部 -->
      <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
        <div class="bg-blue-50 px-6 py-3 border-b">
          <h2 class="text-sm font-semibold text-blue-800">収益</h2>
        </div>
        <table class="w-full text-sm">
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!data.revenue?.length">
              <td colspan="2" class="td text-center text-gray-400">データなし</td>
            </tr>
            <tr v-for="r in data.revenue" :key="r.code" class="hover:bg-gray-50">
              <td class="td">{{ r.name }}</td>
              <td class="td text-right font-medium">{{ fmt(r.amount) }}</td>
            </tr>
          </tbody>
          <tfoot class="bg-blue-50">
            <tr>
              <td class="td font-bold text-blue-800">収益合計</td>
              <td class="td text-right font-bold text-blue-800 text-base">{{ fmt(data.totalRevenue) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- 費用の部 -->
      <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
        <div class="bg-red-50 px-6 py-3 border-b">
          <h2 class="text-sm font-semibold text-red-800">費用</h2>
        </div>
        <table class="w-full text-sm">
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!data.expense?.length">
              <td colspan="2" class="td text-center text-gray-400">データなし</td>
            </tr>
            <tr v-for="r in data.expense" :key="r.code" class="hover:bg-gray-50">
              <td class="td">{{ r.name }}</td>
              <td class="td text-right font-medium">{{ fmt(r.amount) }}</td>
            </tr>
          </tbody>
          <tfoot class="bg-red-50">
            <tr>
              <td class="td font-bold text-red-800">費用合計</td>
              <td class="td text-right font-bold text-red-800 text-base">{{ fmt(data.totalExpense) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- 当期純利益: 正なら緑、負（損失）なら赤 -->
    <div class="mt-6 bg-white rounded-lg border shadow-sm p-6 flex justify-between items-center">
      <h2 class="text-lg font-bold text-gray-800">当期純利益</h2>
      <span class="text-2xl font-bold"
        :class="(data.netIncome ?? 0) >= 0 ? 'text-green-700' : 'text-red-600'">
        {{ fmt(data.netIncome) }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/index.js'

const data = ref({}) // P/L レスポンス全体

const now  = new Date()
/** 期間開始: 当年の1月1日 */
const from = ref(new Date(now.getFullYear(), 0, 1).toISOString().slice(0, 10))
/** 期間終了: 今日 */
const to   = ref(now.toISOString().slice(0, 10))

/** 損益計算書データを取得する */
async function load() {
  const res  = await api.get('/profit-loss', { params: { from: from.value, to: to.value } })
  data.value = res.data
}

function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

onMounted(() => load())
</script>
