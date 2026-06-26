<!--
  入金 登録・編集フォーム

  invoice_id を選択すると請求書への消込が行われる。
  送付済（sent）の請求書のみ消込対象として選択肢に表示する。

  ルート:
    /receipts/create    → 新規登録
    /receipts/:id/edit  → 編集
-->
<template>
  <div class="max-w-xl">
    <form @submit.prevent="submit" class="bg-white rounded-lg border shadow-sm p-6 space-y-4">

      <!-- 取引先 -->
      <div>
        <label class="label">取引先 *</label>
        <select v-model="form.client_id" class="input">
          <option value="">選択</option>
          <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>

      <!-- 対象請求書（消込対象） -->
      <div>
        <label class="label">対象請求書</label>
        <select v-model="form.invoice_id" class="input">
          <option :value="null">なし（単独入金）</option>
          <!-- 送付済みで未入金残がある請求書を選択肢として表示 -->
          <option v-for="inv in invoices" :key="inv.id" :value="inv.id">
            {{ inv.invoice_number }} ({{ fmt(inv.total_amount - inv.paid_amount) }} 未入金)
          </option>
        </select>
      </div>

      <!-- 入金日・入金額（2列） -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">入金日 *</label>
          <input v-model="form.receipt_date" type="date" class="input" />
        </div>
        <div>
          <label class="label">入金額 *</label>
          <input v-model.number="form.amount" type="number" class="input text-right" min="1" />
        </div>
      </div>

      <!-- 入金方法 -->
      <div>
        <label class="label">入金方法</label>
        <select v-model="form.method" class="input">
          <option value="bank_transfer">銀行振込</option>
          <option value="cash">現金</option>
          <option value="check">小切手</option>
          <option value="credit_card">クレジットカード</option>
          <option value="other">その他</option>
        </select>
      </div>

      <!-- 備考 -->
      <div>
        <label class="label">備考</label>
        <textarea v-model="form.notes" class="input" rows="2" />
      </div>

      <!-- フォームアクション -->
      <div class="flex gap-3">
        <button type="submit" :disabled="saving" class="btn-primary">
          {{ id ? '更新する' : '登録する' }}
        </button>
        <a href="#/receipts" class="btn-secondary">キャンセル</a>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/index.js'
import { router, useRoute } from '../../router/index.js'
import { useFlash } from '../../store/flash.js'

const route = useRoute()
const id    = route.params.value.id
const flash = useFlash()

const saving   = ref(false)
const clients  = ref([])
const invoices = ref([]) // 送付済み（消込可能）の請求書リスト

const form = ref({
  client_id:    '',
  invoice_id:   null,
  receipt_date: new Date().toISOString().slice(0, 10),
  amount:       0,
  method:       'bank_transfer',
  notes:        '',
})

function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

onMounted(async () => {
  // 取引先・送付済み請求書を並行取得
  const [cr, ir] = await Promise.all([
    api.get('/clients',  { params: { page: 1 } }),
    api.get('/invoices', { params: { status: 'sent', page: 1 } }),
  ])
  clients.value  = cr.data.data
  invoices.value = ir.data.data

  if (id) {
    const s = await api.get(`/receipts/${id}`)
    Object.assign(form.value, s.data)
  }
})

async function submit() {
  saving.value = true
  try {
    if (id) {
      await api.put(`/receipts/${id}`, form.value)
      flash.success('入金を更新しました。')
    } else {
      await api.post('/receipts', form.value)
      flash.success('入金を登録しました。')
    }
    router.push('#/receipts')
  } catch (e) {
    console.error(e)
  } finally {
    saving.value = false
  }
}
</script>
