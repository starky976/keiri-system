<!--
  支払 登録・編集フォーム

  勘定科目は expense（費用）カテゴリのものだけを選択肢として表示する。

  ルート:
    /payments/create    → 新規登録
    /payments/:id/edit  → 編集
-->
<template>
  <div class="max-w-xl">
    <form @submit.prevent="submit" class="bg-white rounded-lg border shadow-sm p-6 space-y-4">

      <!-- 支払先 -->
      <div>
        <label class="label">支払先 *</label>
        <select v-model="form.client_id" class="input">
          <option value="">選択</option>
          <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>

      <!-- 摘要 -->
      <div>
        <label class="label">摘要 *</label>
        <input v-model="form.description" type="text" class="input" placeholder="外注費 1月分" />
      </div>

      <!-- 支払期日・支払日（2列） -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">支払期日 *</label>
          <input v-model="form.due_date" type="date" class="input" />
        </div>
        <div>
          <label class="label">支払日</label>
          <input v-model="form.payment_date" type="date" class="input" />
        </div>
      </div>

      <!-- 金額・支払方法（2列） -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">金額 *</label>
          <input v-model.number="form.amount" type="number" class="input text-right" min="1" />
        </div>
        <div>
          <label class="label">支払方法</label>
          <select v-model="form.method" class="input">
            <option value="bank_transfer">銀行振込</option>
            <option value="cash">現金</option>
            <option value="check">小切手</option>
            <option value="credit_card">カード</option>
            <option value="other">その他</option>
          </select>
        </div>
      </div>

      <!-- 勘定科目（費用科目のみ表示） -->
      <div>
        <label class="label">勘定科目 *</label>
        <select v-model="form.account_item_id" class="input">
          <option value="">選択</option>
          <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} {{ a.name }}</option>
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
        <a href="#/payments" class="btn-secondary">キャンセル</a>
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
const accounts = ref([]) // 費用勘定科目リスト

const form = ref({
  client_id:       '',
  description:     '',
  due_date:        '',
  payment_date:    '',
  amount:          0,
  method:          'bank_transfer',
  account_item_id: '',
  notes:           '',
})

onMounted(async () => {
  // 取引先・勘定科目を並行取得
  const [cr, ar] = await Promise.all([
    api.get('/clients',       { params: { page: 1 } }),
    api.get('/account-items'),
  ])
  clients.value  = cr.data.data
  // 支払は費用科目のみを対象とする
  accounts.value = ar.data.filter(a => a.category === 'expense')

  if (id) {
    const s = await api.get(`/payments/${id}`)
    Object.assign(form.value, s.data)
  }
})

async function submit() {
  saving.value = true
  try {
    if (id) {
      await api.put(`/payments/${id}`, form.value)
      flash.success('支払を更新しました。')
    } else {
      await api.post('/payments', form.value)
      flash.success('支払を登録しました。')
    }
    router.push('#/payments')
  } catch (e) {
    console.error(e)
  } finally {
    saving.value = false
  }
}
</script>
