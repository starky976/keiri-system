<!--
  総勘定元帳 一覧ページ

  全勘定科目の残高一覧を表示する。
  科目コードをクリックすると、その科目の仕訳明細一覧（ledger/Show）へ遷移する。
  残高がマイナスの場合は赤字で強調表示する。
-->
<template>
  <div>
    <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="th w-24">科目コード</th><th class="th">勘定科目名</th><th class="th">区分</th>
            <th class="th text-right">借方合計</th><th class="th text-right">貸方合計</th>
            <th class="th text-right">残高</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="loading"><td colspan="6" class="td text-center text-gray-400">読み込み中...</td></tr>
          <tr v-else-if="!rows.length"><td colspan="6" class="td text-center text-gray-400">データがありません</td></tr>
          <tr v-for="r in rows" :key="r.id" class="hover:bg-gray-50">
            <td class="td font-mono text-xs">{{ r.code }}</td>
            <td class="td">
              <!-- 科目コードをリンクにして明細ページへ遷移 -->
              <a :href="`#/ledger/${r.code}`" class="text-blue-600 hover:underline">{{ r.name }}</a>
            </td>
            <td class="td">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                {{ r.categoryText }}
              </span>
            </td>
            <td class="td text-right">{{ fmt(r.debitTotal) }}</td>
            <td class="td text-right">{{ fmt(r.creditTotal) }}</td>
            <!-- 残高がマイナスなら赤字 -->
            <td class="td text-right font-medium" :class="r.balance < 0 ? 'text-red-600' : ''">
              {{ fmt(r.balance) }}
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

const rows    = ref([])
const loading = ref(true)

async function load() {
  const res    = await api.get('/ledger')
  rows.value   = res.data
  loading.value = false
}

function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

onMounted(() => load())
</script>
