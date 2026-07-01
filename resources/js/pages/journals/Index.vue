<!--
  仕訳一覧ページ

  仕訳番号・摘要でのキーワード検索と期間フィルターを提供する。
  テーブルには借方・貸方の代表科目名と金額を表示する。
-->
<template>
  <div>

    <!-- エラー表示 -->
    <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
      ⚠ {{ error }}
    </div>
    <div class="flex justify-between items-center mb-4">
      <div class="flex gap-2">
        <input v-model="q" @keyup.enter="load(1)" type="text"
          placeholder="仕訳番号・摘要" class="input w-48" />
        <input v-model="from" type="date" class="input w-36" />
        <span class="self-center text-gray-400">〜</span>
        <input v-model="to" type="date" class="input w-36" />
        <button @click="load(1)" class="btn-secondary">検索</button>
      </div>
      <a href="#/journals/create" class="btn-primary">+ 仕訳入力</a>
    </div>

    <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="th">仕訳番号</th><th class="th">日付</th><th class="th">摘要</th>
            <th class="th">借方科目</th><th class="th">貸方科目</th>
            <th class="th text-right">金額</th><th class="th w-20">操作</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
                    <LoadingSpinner v-if="loading" :colspan="6" />
          <tr v-else-if="!rows.length"><td colspan="7" class="td text-center text-gray-400">データがありません</td></tr>
          <tr v-for="j in rows" :key="j.id" class="hover:bg-gray-50">
            <td class="td font-mono text-xs">
              <a :href="`#/journals/${j.id}/edit`" class="text-blue-600 hover:underline">{{ j.journal_number }}</a>
            </td>
            <td class="td">{{ fmtDate(j.journal_date) }}</td>
            <td class="td">{{ j.description }}</td>
            <!-- 最初の借方・貸方明細の科目名を表示 -->
            <td class="td text-xs">{{ debit(j)?.account_item?.name }}</td>
            <td class="td text-xs">{{ credit(j)?.account_item?.name }}</td>
            <!-- 借方合計金額を代表値として表示 -->
            <td class="td text-right font-medium">{{ fmt(debit(j)?.amount) }}</td>
            <td class="td">
              <div class="flex gap-2">
                <a :href="`#/journals/${j.id}/edit`" class="text-xs text-blue-600 hover:underline">編集</a>
                <button @click="del(j)" class="text-xs text-red-600 hover:underline">削除</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="pagination" @page="load" />
  </div>
</template>

<script setup>
import { fmtDate } from '../../utils/date.js'
import { ref, onMounted } from 'vue'
import LoadingSpinner from '../../components/LoadingSpinner.vue'
import { useAsync }    from '../../composables/useAsync.js'
import Pagination from '../../components/Pagination.vue'
import api        from '../../api/index.js'
import { useFlash } from '../../store/flash.js'

const { loading, error, execute } = useAsync()

const rows       = ref([])
const pagination = ref({})
const q          = ref('')
const from       = ref('')
const to         = ref('')
const flash      = useFlash()

async function load(p = 1) {
  await execute(async () => {
    const res = await api.get('/journals', {
      params: { search: q.value, from: from.value, to: to.value, page: p },
    })
    rows.value       = res.data.data
    pagination.value = res.data
  })
}

async function del(j) {
  if (!confirm(`仕訳「${j.journal_number}」を削除しますか？`)) return
  await api.delete(`/journals/${j.id}`)
  flash.success('削除しました。')
  load()
}

/** 仕訳明細から最初の借方エントリを返す */
function debit(j)  { return j.entries?.find(e => e.side === 'debit') }
/** 仕訳明細から最初の貸方エントリを返す */
function credit(j) { return j.entries?.find(e => e.side === 'credit') }

function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

onMounted(() => load())
</script>
