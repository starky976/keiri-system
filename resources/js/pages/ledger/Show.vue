<!--
  総勘定元帳 明細ページ

  URL パラメータ :code で指定した勘定科目の仕訳明細を時系列で表示する。
  期間フィルターで表示範囲を絞り込める。
  各行の残高は借方-貸方の累計で計算される（残高は API 側で計算）。
-->
<template>
  <div>
    <!-- ヘッダー: 戻るリンク + 期間フィルター -->
    <div class="mb-4 flex items-center gap-4">
      <a href="#/ledger" class="text-sm text-blue-600 hover:underline">← 元帳一覧</a>
      <div class="flex gap-2 ml-auto">
        <input v-model="from" type="date" class="input w-36" />
        <span class="self-center text-gray-400">〜</span>
        <input v-model="to" type="date" class="input w-36" />
        <button @click="load" class="btn-secondary">絞込</button>
      </div>
    </div>

    <!-- 科目情報カード -->
    <div v-if="account" class="mb-4 bg-white rounded-lg border shadow-sm p-4 flex gap-6">
      <div>
        <p class="text-xs text-gray-500">科目コード</p>
        <p class="font-mono font-bold">{{ account.code }}</p>
      </div>
      <div>
        <p class="text-xs text-gray-500">科目名</p>
        <p class="font-bold">{{ account.name }}</p>
      </div>
      <div>
        <p class="text-xs text-gray-500">区分</p>
        <p>{{ account.category }}</p>
      </div>
    </div>

    <!-- 明細テーブル -->
    <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="th">日付</th><th class="th">仕訳番号</th><th class="th">摘要</th>
            <th class="th text-right">借方</th><th class="th text-right">貸方</th>
            <th class="th text-right">残高</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="!entries.length"><td colspan="6" class="td text-center text-gray-400">データがありません</td></tr>
          <tr v-for="e in entries" :key="e.id" class="hover:bg-gray-50">
            <td class="td">{{ e.journalDate }}</td>
            <td class="td font-mono text-xs">{{ e.journalNumber }}</td>
            <td class="td">{{ e.description }}</td>
            <!-- 借方の場合は金額を表示、貸方は空欄 -->
            <td class="td text-right">{{ e.side === 'debit'  ? fmt(e.amount) : '' }}</td>
            <!-- 貸方の場合は金額を表示、借方は空欄 -->
            <td class="td text-right">{{ e.side === 'credit' ? fmt(e.amount) : '' }}</td>
            <!-- 残高がマイナスなら赤字 -->
            <td class="td text-right font-medium" :class="e.balance < 0 ? 'text-red-600' : ''">
              {{ fmt(e.balance) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/index.js'
import { useRoute } from '../../router/index.js'

const route   = useRoute()
/** URL パラメータ :code から勘定科目コードを取得 */
const code    = route.params.value.code
const entries = ref([])     // 仕訳明細一覧
const account = ref(null)   // 科目情報
const from    = ref('')
const to      = ref('')

async function load() {
  const res     = await api.get(`/ledger/${code}`, { params: { from: from.value, to: to.value } })
  account.value = res.data.account
  entries.value = res.data.entries
}

function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

onMounted(() => load())
</script>
