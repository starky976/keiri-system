<!--
  入金一覧ページ

  入金番号・取引先でのキーワード検索と期間フィルターを提供する。
  入金方法は方法コードを日本語に変換して表示する。
-->
<template>
  <div>
    <!-- 検索・フィルター行 -->
    <div class="flex justify-between items-center mb-4">
      <div class="flex gap-2">
        <input v-model="q" @keyup.enter="load(1)" type="text"
          placeholder="入金番号・取引先" class="input w-48" />
        <!-- 期間フィルター（入金日） -->
        <input v-model="from" type="date" class="input w-36" />
        <span class="self-center text-gray-400">〜</span>
        <input v-model="to" type="date" class="input w-36" />
        <button @click="load(1)" class="btn-secondary">検索</button>
      </div>
      <a href="#/receipts/create" class="btn-primary">+ 入金登録</a>
    </div>

    <!-- 入金テーブル -->
    <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="th">入金番号</th><th class="th">入金日</th><th class="th">取引先</th>
            <th class="th">対象請求書</th><th class="th">方法</th>
            <th class="th text-right">入金額</th><th class="th w-16">操作</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="!rows.length"><td colspan="7" class="td text-center text-gray-400">データがありません</td></tr>
          <tr v-for="r in rows" :key="r.id" class="hover:bg-gray-50">
            <td class="td font-mono text-xs">{{ r.receipt_number }}</td>
            <td class="td">{{ r.receipt_date }}</td>
            <td class="td">{{ r.client?.name }}</td>
            <!-- 消込対象請求書番号（なければ「-」） -->
            <td class="td text-xs text-gray-500">{{ r.invoice?.invoice_number ?? '-' }}</td>
            <!-- 入金方法コードを日本語に変換 -->
            <td class="td text-xs">{{ methods[r.method] ?? r.method }}</td>
            <!-- 入金額は緑色で表示 -->
            <td class="td text-right font-medium text-green-700">{{ fmt(r.amount) }}</td>
            <td class="td">
              <a :href="`#/receipts/${r.id}/edit`" class="text-xs text-blue-600 hover:underline">編集</a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="pagination" @page="load" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Pagination from '../../components/Pagination.vue'
import api        from '../../api/index.js'

const rows       = ref([])
const pagination = ref({})
const q          = ref('')
const from       = ref('')
const to         = ref('')

/** 入金方法コード → 日本語ラベルのマッピング */
const methods = {
  bank_transfer: '銀行振込',
  cash:          '現金',
  check:         '小切手',
  credit_card:   'カード',
  other:         'その他',
}

async function load(p = 1) {
  const res = await api.get('/receipts', {
    params: { search: q.value, from: from.value, to: to.value, page: p },
  })
  rows.value       = res.data.data
  pagination.value = res.data
}

function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

onMounted(() => load())
</script>
