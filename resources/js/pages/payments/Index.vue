<!--
  支払一覧ページ

  未承認・承認済・支払済のステータスフィルターと
  キーワード検索（番号・摘要・支払先）を提供する。
-->
<template>
  <div>
    <!-- 検索・フィルター行 -->
    <div class="flex justify-between items-center mb-4">
      <div class="flex gap-2">
        <input v-model="q" @keyup.enter="load(1)" type="text"
          placeholder="番号・摘要・支払先" class="input w-48" />
        <select v-model="status" @change="load(1)" class="input w-32">
          <option value="">全状態</option>
          <option value="pending">未承認</option>
          <option value="approved">承認済</option>
          <option value="paid">支払済</option>
        </select>
        <button @click="load(1)" class="btn-secondary">検索</button>
      </div>
      <a href="#/payments/create" class="btn-primary">+ 支払登録</a>
    </div>
    <!-- エラー表示 -->
    <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
      ⚠ {{ error }}
    </div>


    <!-- 支払テーブル -->
    <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="th">支払番号</th><th class="th">支払期日</th><th class="th">支払先</th>
            <th class="th">摘要</th><th class="th">勘定科目</th>
            <th class="th text-right">金額</th><th class="th">状態</th><th class="th w-16">操作</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
                    <LoadingSpinner v-if="loading" :colspan="6" />
          <tr v-else-if="!rows.length"><td colspan="8" class="td text-center text-gray-400">データがありません</td></tr>
          <tr v-for="r in rows" :key="r.id" class="hover:bg-gray-50">
            <td class="td font-mono text-xs">{{ r.payment_number }}</td>
            <td class="td">{{ r.due_date }}</td>
            <td class="td">{{ r.client?.name }}</td>
            <td class="td">{{ r.description }}</td>
            <td class="td text-xs">{{ r.account_item?.name }}</td>
            <td class="td text-right font-medium">{{ fmt(r.amount) }}</td>
            <td class="td"><StatusBadge :status="r.status" /></td>
            <td class="td">
              <a :href="`#/payments/${r.id}/edit`" class="text-xs text-blue-600 hover:underline">編集</a>
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
import LoadingSpinner from '../../components/LoadingSpinner.vue'
import { useAsync }    from '../../composables/useAsync.js'
import StatusBadge from '../../components/StatusBadge.vue'
import Pagination  from '../../components/Pagination.vue'
import api         from '../../api/index.js'

const { loading, error, execute } = useAsync()

const rows       = ref([])
const pagination = ref({})
const q          = ref('')
const status     = ref('')

async function load(p = 1) {
  await execute(async () => {
    const res = await api.get('/payments', { params: { search: q.value, status: status.value, page: p } })
    rows.value       = res.data.data
    pagination.value = res.data
  })
}

function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

onMounted(() => load())
</script>
