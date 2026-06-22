<template>
  <div class="max-w-3xl space-y-4">
    <div class="bg-white rounded-lg border shadow-sm p-6">
      <div class="grid grid-cols-2 gap-4">
        <div><label class="label">件名 *</label><input v-model="form.title" type="text" class="input" placeholder="1月 交通費精算" /></div>
        <div><label class="label">経費発生日 *</label><input v-model="form.expense_date" type="date" class="input" /></div>
      </div>
      <div class="mt-3"><label class="label">備考</label><textarea v-model="form.notes" class="input" rows="2" /></div>
    </div>
    <div class="bg-white rounded-lg border shadow-sm p-6">
      <div class="flex justify-between items-center mb-4"><h2 class="text-sm font-semibold text-gray-700">明細</h2><button type="button" @click="form.items.push({account_item_id:'',item_date:'',description:'',amount:0,tax_rate:'10'})" class="btn-secondary text-xs py-1">+ 行追加</button></div>
      <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-200 text-xs text-gray-500"><th class="text-left pb-2 pr-2">日付</th><th class="text-left pb-2 pr-2">内容</th><th class="text-left pb-2 pr-2">勘定科目</th><th class="text-right pb-2 pr-2 w-32">金額</th><th class="text-center pb-2 w-16">税率</th><th class="w-6"/></tr></thead>
        <tbody>
          <tr v-for="(item,i) in form.items" :key="i" class="border-b border-gray-100">
            <td class="py-1.5 pr-2 w-36"><input v-model="item.item_date" type="date" class="input text-xs" /></td>
            <td class="py-1.5 pr-2"><input v-model="item.description" type="text" class="input text-xs" placeholder="電車代 渋谷→新宿" /></td>
            <td class="py-1.5 pr-2 w-40"><select v-model="item.account_item_id" class="input text-xs"><option value="">選択</option><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option></select></td>
            <td class="py-1.5 pr-2"><input v-model.number="item.amount" type="number" class="input text-right" min="1" /></td>
            <td class="py-1.5 pr-2 text-center"><select v-model="item.tax_rate" class="input text-xs"><option value="10">10%</option><option value="8">8%</option><option value="0">0%</option></select></td>
            <td class="py-1.5 text-center"><button type="button" @click="form.items.splice(i,1)" v-if="form.items.length>1" class="text-red-400 text-xs">✕</button></td>
          </tr>
        </tbody>
      </table>
      <div class="mt-4 border-t pt-3 flex justify-end text-sm font-bold"><span class="mr-4 text-gray-600">合計</span><span class="text-blue-700">{{ fmt(total) }}</span></div>
    </div>
    <div class="flex gap-3"><button @click="submit" :disabled="saving" class="btn-primary">{{ id?'更新する':'申請する' }}</button><a href="#/expenses" class="btn-secondary">キャンセル</a></div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api/index.js'
import { router, useRoute } from '../../router/index.js'
import { useFlash } from '../../store/flash.js'
const route=useRoute();const id=route.params.value.id;const flash=useFlash();const saving=ref(false);const accounts=ref([])
const form=ref({expense_date:new Date().toISOString().slice(0,10),title:'',notes:'',items:[{account_item_id:'',item_date:new Date().toISOString().slice(0,10),description:'',amount:0,tax_rate:'10'}]})
const total=computed(()=>form.value.items.reduce((s,i)=>s+(i.amount||0),0))
function fmt(v){return new Intl.NumberFormat('ja-JP',{style:'currency',currency:'JPY'}).format(v??0)}
onMounted(async()=>{const r=await api.get('/account-items');accounts.value=r.data.filter(a=>a.category==='expense');if(id){const s=await api.get(`/expenses/${id}`);Object.assign(form.value,s.data)}})
async function submit(){saving.value=true;try{if(id){await api.put(`/expenses/${id}`,form.value);flash.success('経費を更新しました。')}else{await api.post('/expenses',form.value);flash.success('経費を申請しました。')}router.push('#/expenses')}catch(e){console.error(e)}finally{saving.value=false}}
</script>
