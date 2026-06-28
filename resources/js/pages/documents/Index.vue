<!--
  帳票出力ページ
  請求書・領収書・支払明細の帳票を取得し、印刷プレビューを表示する。
  印刷は window.print() を使用してブラウザの PDF 保存機能に委ねる。
-->
<template>
  <div>
    <h1 class="page-title mb-6">帳票出力</h1>

    <!-- 帳票種別選択 -->
    <div class="flex gap-3 mb-6">
      <button v-for="t in types" :key="t.key"
        @click="type = t.key; docId = ''; preview = null"
        :class="['px-4 py-2 rounded-lg border text-sm font-medium transition',
          type === t.key ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300']">
        {{ t.label }}
      </button>
    </div>

    <!-- ID入力 -->
    <div class="flex gap-2 mb-6">
      <input v-model="docId" type="number" :placeholder="`${typeLabel}番号を入力`" class="input w-40" min="1" />
      <button @click="loadPreview" :disabled="!docId || loading" class="btn-primary">
        {{ loading ? '読み込み中...' : 'プレビュー' }}
      </button>
    </div>

    <!-- プレビュー -->
    <div v-if="preview" class="border rounded-xl bg-white shadow">
      <div class="flex justify-between items-center p-4 border-b">
        <span class="font-semibold text-gray-700">プレビュー</span>
        <button @click="print" class="btn-primary">🖨 印刷 / PDF 保存</button>
      </div>
      <div id="print-area" class="p-8">
        <!-- 請求書 -->
        <template v-if="preview.type === 'invoice'">
          <h2 class="text-2xl font-bold text-center mb-6">請 求 書</h2>
          <div class="flex justify-between mb-6">
            <div>
              <p class="font-semibold">{{ preview.document.client?.name }} 御中</p>
              <p class="text-sm text-gray-500">請求番号: {{ preview.document.invoice_number }}</p>
              <p class="text-sm text-gray-500">請求日: {{ preview.document.invoice_date }}</p>
              <p class="text-sm text-gray-500">支払期限: {{ preview.document.due_date }}</p>
            </div>
            <div class="text-right">
              <p class="text-3xl font-bold text-blue-600">{{ fmt(preview.document.total_amount) }}</p>
              <p class="text-sm text-gray-400">（税込）</p>
            </div>
          </div>
          <table class="w-full border-collapse text-sm mb-4">
            <thead><tr class="bg-gray-100">
              <th class="border p-2 text-left">品目</th>
              <th class="border p-2 text-right">数量</th>
              <th class="border p-2 text-right">単価</th>
              <th class="border p-2 text-right">金額</th>
            </tr></thead>
            <tbody>
              <tr v-for="item in preview.document.items" :key="item.id">
                <td class="border p-2">{{ item.description }}</td>
                <td class="border p-2 text-right">{{ item.quantity }}</td>
                <td class="border p-2 text-right font-mono">{{ fmt(item.unit_price) }}</td>
                <td class="border p-2 text-right font-mono">{{ fmt(item.amount) }}</td>
              </tr>
            </tbody>
          </table>
          <div class="text-right text-sm space-y-1">
            <div>小計: {{ fmt(preview.document.subtotal) }}</div>
            <div>消費税: {{ fmt(preview.document.tax_amount) }}</div>
            <div class="font-bold text-base">合計: {{ fmt(preview.document.total_amount) }}</div>
          </div>
        </template>

        <!-- 領収書 -->
        <template v-if="preview.type === 'receipt'">
          <h2 class="text-2xl font-bold text-center mb-6">領 収 書</h2>
          <div class="text-center mb-6">
            <p class="font-semibold text-lg">{{ preview.document.invoice?.client?.name }} 様</p>
            <p class="text-3xl font-bold text-blue-600 my-2">{{ fmt(preview.document.amount) }}</p>
            <p class="text-sm text-gray-500">入金番号: {{ preview.document.receipt_number }}</p>
            <p class="text-sm text-gray-500">入金日: {{ preview.document.receipt_date }}</p>
            <p class="text-sm text-gray-500">摘要: {{ preview.document.description }}</p>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '../../api/index.js'

const type    = ref('invoice')
const docId   = ref('')
const loading = ref(false)
const preview = ref(null)

const types    = [
  { key: 'invoice', label: '請求書' },
  { key: 'receipt', label: '領収書' },
  { key: 'payment', label: '支払明細' },
]
const typeLabel = computed(() => types.find(t => t.key === type.value)?.label ?? '')
const fmt = (v) => Number(v || 0).toLocaleString('ja-JP', { style: 'currency', currency: 'JPY' })

async function loadPreview() {
  loading.value = true
  try {
    const r = await api.get(`/documents/${type.value}/${docId.value}`)
    preview.value = r.data
  } finally { loading.value = false }
}

function print() {
  const orig = document.body.innerHTML
  document.body.innerHTML = document.getElementById('print-area').innerHTML
  window.print()
  document.body.innerHTML = orig
  window.location.reload()
}
</script>
