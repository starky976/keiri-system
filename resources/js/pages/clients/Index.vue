<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <div class="flex gap-2">
        <input v-model="search" @keyup.enter="load(1)" type="text" placeholder="取引先名・コード" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-56" />
        <select v-model="typeFilter" @change="load(1)" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">すべて</option><option value="customer">得意先</option><option value="vendor">仕入先</option><option value="both">両方</option>
        </select>
        <button @click="load(1)" class="btn-secondary">検索</button>
      </div>
      <a href="#/clients/create" class="btn-primary">+ 新規登録</a>
    </div>

    <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="th">コード</th><th class="th">取引先名</th><th class="th">区分</th>
            <th class="th">電話</th><th class="th">担当者</th><th class="th">状態</th><th class="th w-24">操作</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="loading"><td colspan="7" class="td text-center text-gray-400">読み込み中...</td></tr>
          <tr v-else-if="!rows.length"><td colspan="7" class="td text-center text-gray-400">データがありません</td></tr>
          <tr v-for="c in rows" :key="c.id" class="hover:bg-gray-50">
            <td class="td font-mono text-xs">{{ c.code }}</td>
            <td class="td"><a :href="`#/clients/${c.id}/edit`" class="text-blue-600 hover:underline font-medium">{{ c.name }}</a></td>
            <td class="td"><StatusBadge :status="c.type" /></td>
            <td class="td">{{ c.phone||'-' }}</td>
            <td class="td">{{ c.contact_person||'-' }}</td>
            <td class="td"><span class="badge" :class="c.is_active?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500'">{{ c.is_active?'有効':'無効' }}</span></td>
            <td class="td">
              <div class="flex gap-2">
                <a :href="`#/clients/${c.id}/edit`" class="text-blue-600 hover:underline text-xs">編集</a>
                <button @click="del(c)" class="text-red-600 hover:underline text-xs">削除</button>
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
import Pagination from '../../components/Pagination.vue'
import api from '../../api/index.js'
import { useFlash } from '../../store/flash.js'

const rows = ref([])
const pagination = ref({})
const search = ref('')
const typeFilter = ref('')
const loading = ref(false)
const flash = useFlash()

async function load(page = 1) {
  loading.value = true
  const res = await api.get('/clients', { params: { search: search.value, type: typeFilter.value, page } })
  rows.value = res.data.data
  pagination.value = res.data
  loading.value = false
}

async function del(c) {
  if (!confirm(`「${c.name}」を削除しますか？`)) return
  await api.delete(`/clients/${c.id}`)
  flash.success('削除しました。')
  load()
}

onMounted(() => load())
</script>
