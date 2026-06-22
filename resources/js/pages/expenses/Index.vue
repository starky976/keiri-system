<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <div class="flex gap-2"><input v-model="q" @keyup.enter="load(1)" type="text" placeholder="件名" class="input w-48" /><select v-model="status" @change="load(1)" class="input w-32"><option value="">全状態</option><option value="pending">申請中</option><option value="approved">承認済</option><option value="rejected">却下</option><option value="paid">支払済</option></select><button @click="load(1)" class="btn-secondary">検索</button></div>
      <a href="#/expenses/create" class="btn-primary">+ 経費申請</a>
    </div>
    <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50"><tr><th class="th">経費番号</th><th class="th">申請日</th><th class="th">申請者</th><th class="th">件名</th><th class="th text-right">金額</th><th class="th">状態</th><th class="th w-32">操作</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="!rows.length"><td colspan="7" class="td text-center text-gray-400">データがありません</td></tr>
          <tr v-for="e in rows" :key="e.id" class="hover:bg-gray-50">
            <td class="td font-mono text-xs"><a :href="`#/expenses/${e.id}`" class="text-blue-600 hover:underline">{{ e.expense_number }}</a></td>
            <td class="td">{{ e.applied_date }}</td><td class="td">{{ e.user?.name }}</td><td class="td">{{ e.title }}</td>
            <td class="td text-right font-medium">{{ fmt(e.total_amount) }}</td>
            <td class="td"><StatusBadge :status="e.status" /></td>
            <td class="td"><div class="flex gap-2">
              <button v-if="e.status==='pending'" @click="approve(e)" class="text-xs text-green-600 hover:underline">承認</button>
              <a :href="`#/expenses/${e.id}/edit`" class="text-xs text-blue-600 hover:underline">編集</a>
              <button @click="del(e)" class="text-xs text-red-600 hover:underline">削除</button>
            </div></td>
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
const rows=ref([]);const pagination=ref({});const q=ref('');const status=ref('');const flash=useFlash()
async function load(p=1){const res=await api.get('/expenses',{params:{search:q.value,status:status.value,page:p}});rows.value=res.data.data;pagination.value=res.data}
async function approve(e){if(!confirm(`「${e.title}」を承認しますか？`))return;await api.post(`/expenses/${e.id}/approve`);flash.success('承認しました。');load()}
async function del(e){if(!confirm(`「${e.title}」を削除しますか？`))return;await api.delete(`/expenses/${e.id}`);flash.success('削除しました。');load()}
function fmt(v){return new Intl.NumberFormat('ja-JP',{style:'currency',currency:'JPY'}).format(v??0)}
onMounted(()=>load())
</script>
