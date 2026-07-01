<!--
  Dashboard ページ

  経理業務の概況を表示するトップページ。
  マウント時に /api/dashboard を呼び出して以下のデータを取得する:
    - stats: KPI 4指標（当月売上・当月入金・期限超過件数・承認待ち件数）
    - unpaidInvoices: 未入金の請求書一覧
    - pendingExpenses: 承認待ち経費一覧
    - monthlySalesChart: 過去6ヶ月の月別売上データ
-->
<template>
  <div>
    <!-- KPI カード (2列 → 4列) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard label="当月売上"    :value="fmt(stats.monthlySales)"      icon="💹" color="blue"   />
      <StatCard label="当月入金"    :value="fmt(stats.monthlyReceipts)"    icon="💰" color="green"  />
      <StatCard label="期限超過請求" :value="`${stats.overdueCount}件`"    icon="⚠️" color="red"    />
      <StatCard label="承認待ち経費" :value="`${stats.pendingExpenses}件`" icon="🧾" color="yellow" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- 月別売上バーチャート -->
      <div class="bg-white rounded-lg border shadow-sm p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">月別売上推移（過去6ヶ月）</h2>
        <div class="flex items-end gap-2 h-36">
          <!--
            各月のバーを相対的な高さで描画する。
            最大金額を 100% として各バーの height を計算する。
          -->
          <div v-for="item in chart" :key="item.month" class="flex-1 flex flex-col items-center">
            <div class="w-full bg-blue-500 rounded-t transition-all" :style="{ height: barH(item.amount) }" />
            <p class="text-xs text-gray-400 mt-1">{{ item.month.slice(5) }}</p>   <!-- 月部分のみ表示 -->
            <p class="text-xs font-medium text-gray-700">{{ fmtShort(item.amount) }}</p>
          </div>
        </div>
      </div>

      <!-- 未入金請求書一覧 -->
      <div class="bg-white rounded-lg border shadow-sm p-6">
        <div class="flex justify-between items-center mb-3">
          <h2 class="text-sm font-semibold text-gray-700">未入金請求書</h2>
          <a href="#/invoices?status=sent" class="text-xs text-blue-600 hover:underline">すべて見る</a>
        </div>
        <div v-if="!unpaid.length" class="text-sm text-gray-400 text-center py-4">未入金なし</div>
        <div v-for="inv in unpaid" :key="inv.id"
             class="flex justify-between py-2 border-b border-gray-100 last:border-0">
          <div>
            <!-- 請求書番号（編集ページへのリンク） -->
            <a :href="`#/invoices/${inv.id}/edit`" class="text-sm text-blue-600 hover:underline font-medium">
              {{ inv.invoice_number }}
            </a>
            <p class="text-xs text-gray-400">{{ inv.client?.name }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm font-medium">{{ fmt(inv.total_amount) }}</p>
            <!-- 期限超過の場合は赤字で表示 -->
            <p class="text-xs" :class="overdue(inv.due_date) ? 'text-red-500' : 'text-gray-400'">
              {{ fmtDate(inv.due_date) }}
            </p>
          </div>
        </div>
      </div>

      <!-- 承認待ち経費一覧 -->
      <div class="bg-white rounded-lg border shadow-sm p-6">
        <div class="flex justify-between items-center mb-3">
          <h2 class="text-sm font-semibold text-gray-700">承認待ち経費</h2>
          <a href="#/expenses?status=pending" class="text-xs text-blue-600 hover:underline">すべて見る</a>
        </div>
        <div v-if="!pendingExp.length" class="text-sm text-gray-400 text-center py-4">承認待ちなし</div>
        <div v-for="exp in pendingExp" :key="exp.id"
             class="flex justify-between py-2 border-b border-gray-100 last:border-0">
          <div>
            <a :href="`#/expenses/${exp.id}`" class="text-sm text-blue-600 hover:underline font-medium">
              {{ exp.title }}
            </a>
            <p class="text-xs text-gray-400">{{ exp.user?.name }} / {{ fmtDate(exp.applied_date) }}</p>
          </div>
          <p class="text-sm font-medium">{{ fmt(exp.total_amount) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import StatCard from '../components/StatCard.vue'
import api from '../api/index.js'
import { fmtDate } from '../utils/date.js'

/** KPI 指標（当月売上など） */
const stats      = ref({ monthlySales: 0, monthlyReceipts: 0, overdueCount: 0, pendingExpenses: 0 })
/** 未入金請求書一覧 */
const unpaid     = ref([])
/** 承認待ち経費一覧 */
const pendingExp = ref([])
/** 月別売上チャートデータ */
const chart      = ref([])

/** マウント時にダッシュボードAPIを呼び出してデータを取得する */
onMounted(async () => {
  const res = await api.get('/dashboard')
  stats.value      = res.data.stats
  unpaid.value     = res.data.unpaidInvoices
  pendingExp.value = res.data.pendingExpenses
  chart.value      = res.data.monthlySalesChart
})

// ── ユーティリティ関数 ────────────────────────────────────────

/** チャート最大値を返す（ゼロ除算防止のため最低1） */
const maxAmt = () => Math.max(...chart.value.map(i => i.amount), 1)

/** 金額をバーの高さ（%文字列）に変換する。最小 2% で表示 */
function barH(a) { return `${Math.max(a / maxAmt() * 100, 2)}%` }

/** 金額を日本円フォーマット（¥1,234,567）に変換する */
function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

/** 金額を短縮表示（1M / 123K / 500）に変換する */
function fmtShort(v) {
  return v >= 1_000_000 ? `${(v / 1_000_000).toFixed(1)}M`
       : v >= 1_000     ? `${(v / 1_000).toFixed(0)}K`
       : String(v ?? 0)
}

/** 期限日が現在日時より前かどうかを判定する */
function overdue(d) { return new Date(d) < new Date() }
</script>
