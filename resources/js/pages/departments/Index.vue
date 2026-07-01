<!-- 部門管理 一覧・部門別損益レポート -->
<template>
  <div>

    <!-- エラー表示 -->
    <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
      ⚠ {{ error }}
    </div>
    <div class="flex items-center justify-between mb-4">
      <h1 class="page-title">部門管理</h1>
      <button @click="showForm = true" class="btn-primary">＋ 部門追加</button>
    </div>

    <!-- タブ -->
    <div class="flex gap-2 mb-4 border-b">
      <button v-for="t in tabs" :key="t.key" @click="tab = t.key"
        :class="['px-4 py-2 text-sm font-medium border-b-2 transition',
          tab === t.key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500']">
        {{ t.label }}
      </button>
    </div>

    <!-- 部門一覧 -->
    <div v-if="tab === 'list'">
      <table class="table">
        <thead><tr>
          <th class="th">部門コード</th><th class="th">部門名</th>
          <th class="th">説明</th><th class="th">状態</th><th class="th"></th>
        </tr></thead>
        <tbody>
          <tr v-if="!depts.length"><td colspan="5" class="td text-center text-gray-400">部門がありません</td></tr>
          <tr v-for="d in depts" :key="d.id" class="hover:bg-gray-50">
            <td class="td font-mono">{{ d.code }}</td>
            <td class="td font-medium">{{ d.name }}</td>
            <td class="td text-sm text-gray-500">{{ d.description }}</td>
            <td class="td">
              <span :class="d.is_active ? 'badge badge-green' : 'badge badge-gray'">
                {{ d.is_active ? '有効' : '無効' }}
              </span>
            </td>
            <td class="td">
              <button @click="del(d)" class="btn-danger-sm">削除</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- 部門別損益 -->
    <div v-if="tab === 'report'">
      <div class="flex gap-2 mb-4">
        <input v-model="from" type="date" class="input" />
        <span class="self-center">〜</span>
        <input v-model="to" type="date" class="input" />
        <button @click="loadReport" class="btn-primary">集計</button>
      </div>
      <div v-for="d in report" :key="d.department_id" class="card mb-4">
        <h3 class="font-semibold mb-2">{{ d.department_name }}</h3>
        <div class="flex gap-8 text-sm">
          <div>収益合計: <span class="font-mono font-semibold text-green-700">{{ fmt(d.revenue) }}</span></div>
          <div>費用合計: <span class="font-mono font-semibold text-red-600">{{ fmt(d.expense) }}</span></div>
          <div>当期純損益: <span :class="['font-mono font-bold', d.net_income >= 0 ? 'text-green-700' : 'text-red-600']">{{ fmt(d.net_income) }}</span></div>
        </div>
      </div>
      <p v-if="!report.length" class="text-center text-gray-400 py-8">データがありません</p>
    </div>

    <!-- 部門追加モーダル -->
    <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="bg-white rounded-xl shadow-xl p-6 w-96">
        <h3 class="font-semibold mb-4">部門追加</h3>
        <form @submit.prevent="addDept" class="space-y-3">
          <input v-model="newDept.code" placeholder="部門コード（例: D001）" class="input w-full" required />
          <input v-model="newDept.name" placeholder="部門名" class="input w-full" required />
          <textarea v-model="newDept.description" placeholder="説明" rows="2" class="input w-full" />
          <div class="flex gap-2 justify-end">
            <button type="button" @click="showForm = false" class="btn-secondary">キャンセル</button>
            <button type="submit" class="btn-primary">追加</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import LoadingSpinner from '../../components/LoadingSpinner.vue'
import { useAsync }    from '../../composables/useAsync.js'
import api from '../../api/index.js'
import { useFlash } from '../../store/flash.js'

const flash    = useFlash()
const tab      = ref('list')
const tabs     = [{ key: 'list', label: '部門一覧' }, { key: 'report', label: '部門別損益' }]
const { loading, error, execute } = useAsync()

const depts    = ref([])
const report   = ref([])
const showForm = ref(false)
const from     = ref(new Date().getFullYear() + '-01-01')
const to       = ref(new Date().toISOString().slice(0, 10))
const newDept  = ref({ code: '', name: '', description: '' })
const fmt = (v) => Number(v).toLocaleString('ja-JP', { style: 'currency', currency: 'JPY' })

async function loadDepts() {
  await execute(async () => {
    depts.value = (await api.get('/departments')).data
  })
}
async function loadReport() {
  await execute(async () => {
    report.value = (await api.get('/department-report', { params: { from: from.value, to: to.value } })).data.departments
  })
}

async function addDept() {
  await api.post('/departments', newDept.value)
  flash.success('部門を追加しました。')
  showForm.value = false
  newDept.value  = { code: '', name: '', description: '' }
  loadDepts()
}

async function del(d) {
  if (!confirm(`「${d.name}」を削除しますか？`)) return
  await api.delete(`/departments/${d.id}`)
  flash.success('削除しました。')
  loadDepts()
}

onMounted(loadDepts)
</script>
