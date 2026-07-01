<!-- 固定資産 一覧ページ -->
<template>
  <div>

    <!-- エラー表示 -->
    <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
      ⚠ {{ error }}
    </div>
    <div class="flex items-center justify-between mb-4">
      <h1 class="page-title">固定資産管理</h1>
      <button @click="router.push('/assets/create')" class="btn-primary">＋ 資産登録</button>
    </div>
    <div class="flex gap-2 mb-4">
      <input v-model="q" @input="load" placeholder="資産名・番号で検索" class="input w-56" />
      <select v-model="category" @change="load" class="input w-36">
        <option value="">すべての種別</option>
        <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
      </select>
      <label class="flex items-center gap-1 text-sm">
        <input type="checkbox" v-model="activeOnly" @change="load" /> 現役のみ
      </label>
    </div>
    <table class="table">
      <thead><tr>
        <th class="th">資産番号</th><th class="th">名称</th><th class="th">種別</th>
        <th class="th">取得日</th><th class="th text-right">取得価額</th>
        <th class="th text-right">帳簿価額</th><th class="th">状態</th><th class="th"></th>
      </tr></thead>
      <tbody>
        <LoadingSpinner v-if="loading" :colspan="7" />
        <tr v-else-if="!rows.length"><td colspan="8" class="td text-center text-gray-400">データがありません</td></tr>
        <tr v-for="a in rows" :key="a.id" class="hover:bg-gray-50">
          <td class="td font-mono text-sm">{{ a.asset_number }}</td>
          <td class="td font-medium">{{ a.name }}</td>
          <td class="td text-sm">{{ categoryLabel(a.category) }}</td>
          <td class="td text-sm">{{ a.acquisition_date }}</td>
          <td class="td text-right font-mono">{{ fmt(a.acquisition_amount) }}</td>
          <td class="td text-right font-mono">{{ fmt(bookValue(a)) }}</td>
          <td class="td">
            <span v-if="a.disposal_date" class="badge badge-gray">廃棄済</span>
            <span v-else class="badge badge-green">使用中</span>
          </td>
          <td class="td">
            <button @click="router.push(`/assets/${a.id}`)" class="btn-secondary-sm mr-1">詳細</button>
            <button @click="router.push(`/assets/${a.id}/edit`)" class="btn-secondary-sm">編集</button>
          </td>
        </tr>
      </tbody>
    </table>
    <Pagination :meta="pagination" @change="load" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import LoadingSpinner from '../../components/LoadingSpinner.vue'
import { useAsync }    from '../../composables/useAsync.js'
import api from '../../api/index.js'
import { router } from '../../router/index.js'
import Pagination from '../../components/Pagination.vue'

const q          = ref('')
const category   = ref('')
const activeOnly = ref(false)
const { loading, error, execute } = useAsync()

const rows       = ref([])
const pagination = ref({})

const categories = [
  { value: 'building', label: '建物' },
  { value: 'vehicle',  label: '車両' },
  { value: 'equipment',label: '器具備品' },
  { value: 'software', label: 'ソフトウェア' },
  { value: 'land',     label: '土地' },
]
const categoryLabel = (v) => categories.find(c => c.value === v)?.label ?? v
const fmt = (v) => Number(v).toLocaleString('ja-JP', { style: 'currency', currency: 'JPY' })
const bookValue = (a) => {
  const years = new Date().getFullYear() - new Date(a.acquisition_date).getFullYear()
  const annual = (a.acquisition_amount - a.residual_value) / a.useful_life
  return Math.max(a.residual_value, a.acquisition_amount - annual * years)
}

async function load(p = 1) {
  await execute(async () => {
    const r = await api.get('/fixed-assets', {
      params: { search: q.value, category: category.value, active: activeOnly.value ? 1 : 0, page: p }
    })
    rows.value       = r.data.data
    pagination.value = r.data
  })
}
onMounted(load)
</script>
