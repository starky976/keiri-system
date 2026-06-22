<template>
  <div class="max-w-4xl space-y-4">
    <div class="bg-white rounded-lg border shadow-sm p-6">
      <div class="grid grid-cols-2 gap-4">
        <div><label class="label">取引先 *</label><select v-model="form.client_id" class="input"><option value="">選択</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
        <div><label class="label">請求日 *</label><input v-model="form.invoice_date" type="date" class="input" /></div>
        <div><label class="label">支払期限 *</label><input v-model="form.due_date" type="date" class="input" /></div>
        <div><label class="label">備考</label><input v-model="form.notes" type="text" class="input" /></div>
      </div>
    </div>
    <div class="bg-white rounded-lg border shadow-sm p-6">
      <div class="flex justify-between items-center mb-4"><h2 class="text-sm font-semibold text-gray-700">明細</h2><button type="button" @click="form.items.push({item_name:'',unit_price:0,quantity:1,unit:'式',amount:0,tax_rate:'10'})" class="btn-secondary text-xs py-1">+ 行追加</button></div>
      <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-200 text-xs text-gray-500"><th class="text-left pb-2 pr-2 w-2/5">品目名</th><th class="text-right pb-2 pr-2">単価</th><th class="text-right pb-2 pr-2 w-20">数量</th><th class="text-left pb-2 pr-2 w-12">単位</th><th class="text-right pb-2 pr-2 w-20">税率</th><th class="text-right pb-2 w-1/5">金額</th><th class="w-6"/></tr></thead>
        <tbody>
          <tr v-for="(item,i) in form.items" :key="i" class="border-b border-gray-100">
            <td class="py-1.5 pr-2"><input v-model="item.item_name" type="text" class="input" /></td>
            <td class="py-1.5 pr-2"><input v-model.number="item.unit_price" @input="calc(item)" type="number" class="input text-right" min="0" /></td>
            <td class="py-1.5 pr-2"><input v-model.number="item.quantity" @input="calc(item)" type="number" class="input text-right" min="0.01" step="0.01" /></td>
            <td class="py-1.5 pr-2"><input v-model="item.unit" type="text" class="input" placeholder="式" /></td>
            <td class="py-1.5 pr-2"><select v-model="item.tax_rate" class="input text-xs"><option value="10">10%</option><option value="8">8%</option><option value="0">0%</option></select></td>
            <td class="py-1.5 text-right font-medium">{{ fmt(item.amount) }}</td>
            <td class="py-1.5 text-center"><button type="button" @click="form.items.splice(i,1)" v-if="form.items.length>1" class="text-red-400 text-xs">✕</button></td>
          </tr>
        </tbody>
      </table>
      <div class="mt-4 border-t pt-4 flex flex-col items-end gap-1 text-sm">
        <div class="flex justify-between w-52"><span class="text-gray-600">小計</span><span>{{ fmt(subtotal) }}</span></div>
        <div class="flex justify-between w-52"><span class="text-gray-600">消費税</span><span>{{ fmt(tax) }}</span></div>
        <div class="flex justify-between w-52 text-base font-bold border-t pt-2 mt-1"><span>合計</span><span class="text-blue-700">{{ fmt(total) }}</span></div>
      </div>
    </div>
    <div class="flex gap-3"><button @click="submit" :disabled="saving" class="btn-primary">{{ id ? '更新する' : '作成する' }}</button><a href="#/invoices" class="btn-secondary">キャンセル</a></div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api/index.js'
import { router, useRoute } from '../../router/index.js'
import { useFlash } from '../../store/flash.js'
const route=useRoute();const id=route.params.value.id;const flash=useFlash();const saving=ref(false);const clients=ref([])
const form=ref({client_id:'',invoice_date:new Date().toISOString().slice(0,10),due_date:'',notes:'',items:[{item_name:'',unit_price:0,quantity:1,unit:'式',amount:0,tax_rate:'10'}]})
const subtotal=computed(()=>form.value.items.reduce((s,i)=>s+(i.amount??0),0))
const tax=computed(()=>Math.floor(form.value.items.reduce((s,i)=>s+(i.amount??0)*(parseInt(i.tax_rate??0)/100),0)))
const total=computed(()=>subtotal.value+tax.value)
function calc(item){item.amount=Math.round((item.unit_price??0)*(item.quantity??0))}
function fmt(v){return new Intl.NumberFormat('ja-JP',{style:'currency',currency:'JPY'}).format(v??0)}
onMounted(async()=>{const r=await api.get('/clients',{params:{page:1}});clients.value=r.data.data;if(id){const s=await api.get(`/invoices/${id}`);Object.assign(form.value,s.data)}})
async function submit(){saving.value=true;try{if(id){await api.put(`/invoices/${id}`,form.value);flash.success('請求書を更新しました。')}else{await api.post('/invoices',form.value);flash.success('請求書を作成しました。')}router.push('#/invoices')}catch(e){console.error(e)}finally{saving.value=false}}
</script>
