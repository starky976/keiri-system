<!--
  売上 登録・編集フォーム

  明細行（items）を動的に追加・削除できる。
  小計・消費税・合計はリアルタイムで自動計算される。

  ルート:
    /sales/create    → 新規登録
    /sales/:id/edit  → 編集
-->
<template>
  <div class="max-w-4xl space-y-4">

    <!-- 基本情報カード -->
    <div class="bg-white rounded-lg border shadow-sm p-6">
      <h2 class="text-sm font-semibold text-gray-700 mb-4">基本情報</h2>
      <div class="grid grid-cols-2 gap-4">
        <!-- 取引先 -->
        <div>
          <label class="label">取引先 *</label>
          <select v-model="form.client_id" class="input">
            <option value="">選択</option>
            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }} ({{ c.code }})</option>
          </select>
          <p v-if="err.client_id" class="err">{{ err.client_id[0] }}</p>
        </div>
        <!-- 売上日 -->
        <div>
          <label class="label">売上日 *</label>
          <input v-model="form.sale_date" type="date" class="input" />
          <p v-if="err.sale_date" class="err">{{ err.sale_date[0] }}</p>
        </div>
        <!-- 件名 -->
        <div>
          <label class="label">件名 *</label>
          <input v-model="form.description" type="text" class="input" placeholder="システム開発費 1月分" />
          <p v-if="err.description" class="err">{{ err.description[0] }}</p>
        </div>
        <!-- 消費税率（全明細共通） -->
        <div>
          <label class="label">消費税率</label>
          <select v-model="form.tax_rate" class="input">
            <option value="10">10%</option>
            <option value="8">8%（軽減）</option>
            <option value="0">0%（非課税）</option>
          </select>
        </div>
      </div>
    </div>

    <!-- 明細カード -->
    <div class="bg-white rounded-lg border shadow-sm p-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-sm font-semibold text-gray-700">明細</h2>
        <!-- 行追加ボタン -->
        <button type="button" @click="addItem" class="btn-secondary text-xs py-1">+ 行追加</button>
      </div>
      <p v-if="err.items" class="err mb-2">{{ err.items[0] }}</p>

      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200 text-xs text-gray-500">
            <th class="text-left pb-2 pr-2 w-2/5">品目名</th>
            <th class="text-right pb-2 pr-2 w-1/5">単価</th>
            <th class="text-right pb-2 pr-2 w-20">数量</th>
            <th class="text-left pb-2 pr-2 w-12">単位</th>
            <th class="text-right pb-2 w-1/5">金額</th>
            <th class="w-6" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, i) in form.items" :key="i" class="border-b border-gray-100">
            <td class="py-1.5 pr-2">
              <input v-model="item.item_name" type="text" class="input" placeholder="品目名" />
            </td>
            <!-- 単価: 変更時に金額を再計算 -->
            <td class="py-1.5 pr-2">
              <input v-model.number="item.unit_price" @input="calc(item)" type="number" class="input text-right" min="0" />
            </td>
            <!-- 数量: 変更時に金額を再計算 -->
            <td class="py-1.5 pr-2">
              <input v-model.number="item.quantity" @input="calc(item)" type="number" class="input text-right" min="0.01" step="0.01" />
            </td>
            <td class="py-1.5 pr-2">
              <input v-model="item.unit" type="text" class="input" placeholder="式" />
            </td>
            <td class="py-1.5 text-right font-medium">{{ fmt(item.amount) }}</td>
            <!-- 行削除ボタン（1行以下では表示しない） -->
            <td class="py-1.5 text-center">
              <button type="button" @click="form.items.splice(i, 1)"
                v-if="form.items.length > 1" class="text-red-400 hover:text-red-600 text-xs">✕</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- 小計・税・合計サマリー -->
      <div class="mt-4 border-t pt-4 flex flex-col items-end gap-1 text-sm">
        <div class="flex justify-between w-52">
          <span class="text-gray-600">小計</span>
          <span>{{ fmt(subtotal) }}</span>
        </div>
        <div class="flex justify-between w-52">
          <span class="text-gray-600">消費税 ({{ form.tax_rate }}%)</span>
          <span>{{ fmt(tax) }}</span>
        </div>
        <div class="flex justify-between w-52 text-base font-bold border-t pt-2 mt-1">
          <span>合計</span>
          <span class="text-blue-700">{{ fmt(total) }}</span>
        </div>
      </div>
    </div>

    <!-- 備考 -->
    <div class="bg-white rounded-lg border shadow-sm p-4">
      <label class="label">備考</label>
      <textarea v-model="form.notes" class="input" rows="2" />
    </div>

    <!-- フォームアクション -->
    <div class="flex gap-3">
      <button @click="submit" :disabled="saving" class="btn-primary">
        {{ id ? '更新する' : '登録する' }}
      </button>
      <a href="#/sales" class="btn-secondary">キャンセル</a>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api/index.js'
import { router, useRoute } from '../../router/index.js'
import { useFlash } from '../../store/flash.js'

const route = useRoute()
const id    = route.params.value.id
const flash = useFlash()

const saving  = ref(false)
const err     = ref({})
const clients = ref([]) // 取引先選択肢

/** フォームの初期値 */
const form = ref({
  client_id:   '',
  sale_date:   new Date().toISOString().slice(0, 10),
  description: '',
  tax_rate:    '10',
  notes:       '',
  items: [{ item_name: '', unit_price: 0, quantity: 1, unit: '式', amount: 0 }],
})

// ── リアルタイム金額計算 ──────────────────────────────────────
/** 小計: 全明細 amount の合計 */
const subtotal = computed(() => form.value.items.reduce((s, i) => s + (i.amount ?? 0), 0))
/** 消費税額（切り捨て） */
const tax = computed(() => Math.floor(subtotal.value * Number(form.value.tax_rate) / 100))
/** 税込合計 */
const total = computed(() => subtotal.value + tax.value)

/** 1明細の金額を再計算する（単価 × 数量） */
function calc(item) { item.amount = Math.round((item.unit_price ?? 0) * (item.quantity ?? 0)) }

/** 明細行を1行追加する */
function addItem() {
  form.value.items.push({ item_name: '', unit_price: 0, quantity: 1, unit: '式', amount: 0 })
}

/** 日本円フォーマット */
function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

/** マウント時: 取引先一覧取得 & 編集時は既存データをセット */
onMounted(async () => {
  const r = await api.get('/clients', { params: { page: 1 } })
  clients.value = r.data.data
  if (id) {
    const s = await api.get(`/sales/${id}`)
    Object.assign(form.value, s.data)
  }
})

/** フォーム送信（登録/更新） */
async function submit() {
  saving.value = true
  err.value    = {}
  try {
    if (id) {
      await api.put(`/sales/${id}`, form.value)
      flash.success('売上を更新しました。')
    } else {
      await api.post('/sales', form.value)
      flash.success('売上を登録しました。')
    }
    router.push('#/sales')
  } catch (e) {
    err.value = e.response?.data?.errors ?? {}
  } finally {
    saving.value = false
  }
}
</script>
