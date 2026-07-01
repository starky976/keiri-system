<!--
  予算管理 一覧・予実比較ページ
  タブ切り替えで「予算一覧」と「予実比較」を表示する。
-->
<template>
  <div>

    <!-- エラー表示 -->
    <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
      ⚠ {{ error }}
    </div>
    <div class="flex items-center justify-between mb-4">
      <h1 class="page-title">予算管理</h1>
      <div class="flex gap-2">
        <select v-model="year" @change="load" class="input w-28">
          <option v-for="y in years" :key="y" :value="y">{{ y }}年度</option>
        </select>
        <select v-model="month" @change="load" class="input w-24">
          <option value="">年次</option>
          <option v-for="m in 12" :key="m" :value="m">{{ m }}月</option>
        </select>
        <button @click="router.push('/budgets/create')" class="btn-primary">＋ 予算登録</button>
      </div>
    </div>

    <!-- タブ -->
    <div class="flex gap-2 mb-4 border-b">
      <button v-for="t in tabs" :key="t.key"
        @click="tab = t.key"
        :class="['px-4 py-2 text-sm font-medium border-b-2 transition',
          tab === t.key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500']">
        {{ t.label }}
      </button>
    </div>

    <!-- 予算一覧 -->
    <div v-if="tab === 'list'">
      <div v-if="loading" class="text-center py-10 text-gray-400">読み込み中...</div>
      <table v-else class="table">
        <thead><tr>
          <th class="th">勘定科目</th><th class="th">部門</th>
          <th class="th text-right">予算額</th><th class="th">摘要</th><th class="th"></th>
        </tr></thead>
        <tbody>
                    <LoadingSpinner v-if="loading" :colspan="6" />
          <tr v-else-if="!rows.length"><td colspan="5" class="td text-center text-gray-400">データがありません</td></tr>
          <tr v-for="b in rows" :key="b.id" class="hover:bg-gray-50">
            <td class="td">{{ b.account_item?.name }}</td>
            <td class="td">{{ b.department?.name ?? '─' }}</td>
            <td class="td text-right font-mono">{{ fmt(b.amount) }}</td>
            <td class="td text-sm text-gray-500">{{ b.note }}</td>
            <td class="td">
              <button @click="del(b)" class="btn-danger-sm">削除</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- 予実比較 -->
    <div v-if="tab === 'comparison'">
      <div v-if="loading" class="text-center py-10 text-gray-400">読み込み中...</div>
      <table v-else class="table">
        <thead><tr>
          <th class="th">勘定科目</th>
          <th class="th text-right">予算額</th>
          <th class="th text-right">実績額</th>
          <th class="th text-right">差異</th>
          <th class="th text-right">達成率</th>
        </tr></thead>
        <tbody>
          <tr v-if="!comparison.length"><td colspan="5" class="td text-center text-gray-400">データがありません</td></tr>
          <tr v-for="r in comparison" :key="r.account_item_id" class="hover:bg-gray-50">
            <td class="td">{{ r.account_item_name }}</td>
            <td class="td text-right font-mono">{{ fmt(r.budget_amount) }}</td>
            <td class="td text-right font-mono">{{ fmt(r.actual_amount) }}</td>
            <td class="td text-right font-mono" :class="r.variance < 0 ? 'text-red-600' : 'text-green-700'">
              {{ fmt(r.variance) }}
            </td>
            <td class="td text-right">
              <span v-if="r.achievement_rate !== null">{{ r.achievement_rate }}%</span>
              <span v-else class="text-gray-400">─</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import LoadingSpinner from '../../components/LoadingSpinner.vue'
import { useAsync }    from '../../composables/useAsync.js'
import api from '../../api/index.js'
import { router } from '../../router/index.js'
import { useFlash } from '../../store/flash.js'

const flash = useFlash()
const tab   = ref('list')
const tabs  = [{ key: 'list', label: '予算一覧' }, { key: 'comparison', label: '予実比較' }]

const year  = ref(new Date().getFullYear())
const month = ref('')
const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - 2 + i)

const { loading, error, execute } = useAsync()

const rows       = ref([])
const comparison = ref([])

const fmt = (v) => Number(v).toLocaleString('ja-JP', { style: 'currency', currency: 'JPY' })

async function load() {
  await execute(async () => {
    const params = { fiscal_year: year.value, month: month.value || undefined }
    if (tab.value === 'list') {
      const r = await api.get('/budgets', { params })
      rows.value = r.data.data
    } else {
      const r = await api.get('/budgets-comparison', { params })
      comparison.value = r.data.rows
    }
  })
}

async function del(b) {
  if (!confirm(`「${b.account_item?.name}」の予算を削除しますか？`)) return
  await api.delete(`/budgets/${b.id}`)
  flash.success('削除しました。')
  load()
}

onMounted(load)
</script>
