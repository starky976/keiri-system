<!--
  売上一覧ページ

  売上番号・取引先・件名でのキーワード検索、ステータスフィルター、
  期間フィルターを組み合わせて絞り込みができる。
  削除は論理削除（SoftDelete）。
-->
<template>
  <div>
    <!-- 検索・フィルター行 -->
    <div class="flex justify-between items-center mb-4">
      <div class="flex gap-2 flex-wrap">
        <!-- キーワード検索 -->
        <input v-model="q" @keyup.enter="load(1)" type="text"
          placeholder="番号・件名・取引先" class="input w-48" />

        <!-- ステータスフィルター -->
        <select v-model="status" @change="load(1)" class="input w-32">
          <option value="">全状態</option>
          <option value="pending">未請求</option>
          <option value="invoiced">請求済</option>
          <option value="paid">入金済</option>
        </select>

        <!-- 期間フィルター（売上日） -->
        <input v-model="from" type="date" class="input w-36" />
        <span class="self-center text-gray-400">〜</span>
        <input v-model="to" type="date" class="input w-36" />

        <button @click="load(1)" class="btn-secondary">検索</button>
      </div>
      <a href="#/sales/create" class="btn-primary">+ 売上登録</a>
    </div>

    <!-- 売上テーブル -->
    <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="th">売上番号</th><th class="th">売上日</th><th class="th">取引先</th>
            <th class="th">件名</th><th class="th text-right">金額(税込)</th>
            <th class="th">状態</th><th class="th w-20">操作</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="!rows.length"><td colspan="7" class="td text-center text-gray-400">データがありません</td></tr>
          <tr v-for="s in rows" :key="s.id" class="hover:bg-gray-50">
            <td class="td font-mono text-xs">
              <a :href="`#/sales/${s.id}/edit`" class="text-blue-600 hover:underline">{{ s.sale_number }}</a>
            </td>
            <td class="td">{{ s.sale_date }}</td>
            <td class="td">{{ s.client?.name }}</td>
            <td class="td">{{ s.description }}</td>
            <td class="td text-right font-medium">{{ fmt(s.total_amount) }}</td>
            <td class="td"><StatusBadge :status="s.status" /></td>
            <td class="td">
              <div class="flex gap-2">
                <a :href="`#/sales/${s.id}/edit`" class="text-blue-600 text-xs hover:underline">編集</a>
                <button @click="del(s)" class="text-red-600 text-xs hover:underline">削除</button>
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
import { ref, onMounted } from 'vue'
import StatusBadge from '../../components/StatusBadge.vue'
import Pagination  from '../../components/Pagination.vue'
import api         from '../../api/index.js'
import { useFlash } from '../../store/flash.js'

const rows       = ref([])  // テーブル表示データ
const pagination = ref({})  // ページネーション情報
const q          = ref('')  // 検索キーワード
const status     = ref('')  // ステータスフィルター
const from       = ref('')  // 期間開始日
const to         = ref('')  // 期間終了日
const flash      = useFlash()

/** 売上一覧を取得する */
async function load(p = 1) {
  const res = await api.get('/sales', {
    params: { search: q.value, status: status.value, from: from.value, to: to.value, page: p },
  })
  rows.value       = res.data.data
  pagination.value = res.data
}

/** 売上を論理削除する */
async function del(s) {
  if (!confirm(`売上「${s.sale_number}」を削除しますか？`)) return
  await api.delete(`/sales/${s.id}`)
  flash.success('削除しました。')
  load()
}

/** 日本円フォーマット */
function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

onMounted(() => load())
</script>
