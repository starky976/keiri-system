<template>
  <div class="max-w-4xl space-y-4">
    <div class="bg-white rounded-lg border shadow-sm p-6">
      <div class="grid grid-cols-2 gap-4">
        <div><label class="label">仕訳日付 *</label><input v-model="form.journal_date" type="date" class="input" /></div>
        <div><label class="label">摘要 *</label><input v-model="form.description" type="text" class="input" placeholder="売掛金計上 ○○社" /></div>
      </div>
    </div>
    <div class="bg-white rounded-lg border shadow-sm p-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-sm font-semibold text-gray-700">仕訳明細</h2>
        <button type="button" @click="form.entries.push({side:'debit',account_item_id:'',amount:0,description:''})" class="btn-secondary text-xs py-1">+ 行追加</button>
      </div>
      <p v-if="errMsg" class="text-red-600 text-sm mb-3">{{ errMsg }}</p>
      <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-200 text-xs text-gray-500"><th class="text-left pb-2 pr-2 w-24">借/貸</th><th class="text-left pb-2 pr-2">勘定科目</th><th class="text-right pb-2 pr-2 w-40">金額</th><th class="text-left pb-2 w-1/3">摘要</th><th class="w-6"/></tr></thead>
        <tbody>
          <tr v-for="(e,i) in form.entries" :key="i" class="border-b border-gray-100">
            <td class="py-1.5 pr-2"><select v-model="e.side" class="input text-xs" :class="e.side==='debit'?'bg-blue-50':'bg-red-50'"><option value="debit">借方</option><option value="credit">貸方</option></select></td>
            <td class="py-1.5 pr-2">
              <select v-model="e.account_item_id" class="input text-xs">
                <option value="">選択</option>
                <optgroup v-for="g in grouped" :key="g.label" :label="g.label">
                  <option v-for="a in g.items" :key="a.id" :value="a.id">{{ a.code }} {{ a.name }}</option>
                </optgroup>
              </select>
            </td>
            <td class="py-1.5 pr-2"><input v-model.number="e.amount" type="number" class="input text-right" min="1" /></td>
            <td class="py-1.5 pr-2"><input v-model="e.description" type="text" class="input text-xs" placeholder="任意" /></td>
            <td class="py-1.5 text-center"><button type="button" @click="form.entries.splice(i,1)" v-if="form.entries.length>2" class="text-red-400 text-xs">✕</button></td>
          </tr>
        </tbody>
      </table>
      <!-- 貸借合計 -->
      <div class="mt-4 border-t pt-4 flex justify-end gap-8 text-sm">
        <div class="text-center"><p class="text-gray-500 text-xs">借方合計</p><p class="font-bold text-blue-700">{{ fmt(debitTotal) }}</p></div>
        <div class="text-center"><p class="text-gray-500 text-xs">貸方合計</p><p class="font-bold text-red-700">{{ fmt(creditTotal) }}</p></div>
        <div class="text-center"><p class="text-gray-500 text-xs">差額</p><p class="font-bold" :class="balanced?'text-green-600':'text-orange-600'">{{ balanced ? '✓ 一致' : fmt(Math.abs(debitTotal-creditTotal)) }}</p></div>
      </div>
    </div>
    <div class="flex gap-3">
      <button @click="submit" :disabled="saving||!balanced" class="btn-primary disabled:opacity-50">{{ id?'更新する':'登録する' }}</button>
      <a href="#/journals" class="btn-secondary">キャンセル</a>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api/index.js'
import { router, useRoute } from '../../router/index.js'
import { useFlash } from '../../store/flash.js'
const route=useRoute();const id=route.params.value.id;const flash=useFlash();const saving=ref(false);const errMsg=ref('');const accounts=ref([])
const form=ref({journal_date:new Date().toISOString().slice(0,10),description:'',entries:[{side:'debit',account_item_id:'',amount:0,description:''},{side:'credit',account_item_id:'',amount:0,description:''}]})
const catLabels={asset:'資産',liability:'負債',equity:'純資産',revenue:'収益',expense:'費用'}
const grouped=computed(()=>{const g={};for(const a of accounts.value){if(!g[a.category])g[a.category]=[];g[a.category].push(a)}return Object.entries(g).map(([c,items])=>({label:catLabels[c]??c,items}))})
const debitTotal=computed(()=>form.value.entries.filter(e=>e.side==='debit').reduce((s,e)=>s+(e.amount||0),0))
const creditTotal=computed(()=>form.value.entries.filter(e=>e.side==='credit').reduce((s,e)=>s+(e.amount||0),0))
const balanced=computed(()=>debitTotal.value>0&&debitTotal.value===creditTotal.value)
function fmt(v){return new Intl.NumberFormat('ja-JP',{style:'currency',currency:'JPY'}).format(v??0)}
onMounted(async()=>{const r=await api.get('/account-items');accounts.value=r.data;if(id){const j=await api.get(`/journals/${id}`);Object.assign(form.value,j.data)}})
async function submit(){saving.value=true;errMsg.value='';try{if(id){await api.put(`/journals/${id}`,form.value);flash.success('仕訳を更新しました。')}else{await api.post('/journals',form.value);flash.success('仕訳を登録しました。')}router.push('#/journals')}catch(e){errMsg.value=e.response?.data?.errors?.entries?.[0]??'エラーが発生しました。'}finally{saving.value=false}}
</script>
