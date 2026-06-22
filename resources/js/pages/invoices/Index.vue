<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <div class="flex gap-2">
        <input v-model="q" @keyup.enter="load(1)" type="text" placeholder="請求書番号・取引先" class="input w-48" />
        <select v-model="status" @change="load(1)" class="input w-36">
          <option value="">全状態</option><option value="draft">下書き</option><option value="sent">送付済</option><option value="paid">入金済</option><option value="overdue">期限超過</option>
        </select>
        <button @click="load(1)" class="btn-secondary">検索</button>
      </div>
      <a href="#/invoices/create" class="btn-primary">+ 請求書作成</a>
    </div>
    <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50"><tr><th class="th">請求書番号</th><th class="th">請求日</th><th class="th">取引先</th><th class="th">支払期限</th><th class="th text-right">金額</th><th class="th text-right">未入金</th><th class="th">状態</th><th class="th w-28">操作</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="!rows.length"><td colspan="8" class="td text-center text-gray-400">データがありません</td></tr>
          <tr v-for="r in rows" :key="r.id" class="hover:bg-gray-50">
            <td class="td font-mono text-xs"><a :href="`#/invoices/${r.id}/edit`" class="text-blue-600 hover:underline">{{ r.invoice_number }}</a></td>
            <td class="td">{{ r.invoice_date }}</td><td class="td">{{ r.client?.name }}</td>
            <td class="td" :class="ov(r.due_date,r.status)?'text-red-600 font-medium':''">{{ r.due_date }}</td>
            <td class="td text-right font-medium">{{ fmt(r.total_amount) }}</td>
            <td class="td text-right" :class="(r.total_amount-r.paid_amount)>0?'text-orange-600':''">{{ fmt(r.total_amount-r.paid_amount) }}</td>
            <td class="td"><StatusBadge :status="r.status" /></td>
            <td class="td"><div class="flex gap-1">
              <button v-if="r.status==='draft'" @click="send(r)" class="text-xs text-blue-600 hover:underline">送付</button>
              <a :href="`#/invoices/${r.id}/edit`" class="text-xs text-blue-600 hover:underline">編集</a>
              <button @click="del(r)" class="text-xs text-red-600 hover:underline">削除</button>
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
async function load(p=1){const res=await api.get('/invoices',{params:{search:q.value,status:status.value,page:p}});rows.value=res.data.data;pagination.value=res.data}
async function send(r){if(!confirm('送付済みにしますか？'))return;await api.post(`/invoices/${r.id}/send`);flash.success('送付済みにしました。');load()}
async function del(r){if(!confirm(`請求書「${r.invoice_number}」を削除しますか？`))return;await api.delete(`/invoices/${r.id}`);flash.success('削除しました。');load()}
function fmt(v){return new Intl.NumberFormat('ja-JP',{style:'currency',currency:'JPY'}).format(v??0)}
function ov(d,s){return s!=='paid'&&new Date(d)<new Date()}
onMounted(()=>load())
</script>
