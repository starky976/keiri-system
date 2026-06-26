<!--
  仕訳 登録・編集フォーム

  複式簿記の仕訳を入力する。複数行の借方・貸方明細を動的に追加できる。
  借方合計 === 貸方合計でないと登録ボタンが無効化される（フロント側バランスチェック）。
  勘定科目は category でグループ化して <optgroup> で表示する。

  ルート:
    /journals/create    → 新規入力
    /journals/:id/edit  → 編集
-->
<template>
  <div class="max-w-4xl space-y-4">

    <!-- 仕訳基本情報 -->
    <div class="bg-white rounded-lg border shadow-sm p-6">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">仕訳日付 *</label>
          <input v-model="form.journal_date" type="date" class="input" />
        </div>
        <div>
          <label class="label">摘要 *</label>
          <input v-model="form.description" type="text" class="input" placeholder="売掛金計上 ○○社" />
        </div>
      </div>
    </div>

    <!-- 仕訳明細テーブル -->
    <div class="bg-white rounded-lg border shadow-sm p-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-sm font-semibold text-gray-700">仕訳明細</h2>
        <!-- 借方行追加 -->
        <button type="button"
          @click="form.entries.push({ side: 'debit', account_item_id: '', amount: 0, description: '' })"
          class="btn-secondary text-xs py-1">+ 行追加</button>
      </div>

      <!-- バランスエラーメッセージ -->
      <p v-if="errMsg" class="text-red-600 text-sm mb-3">{{ errMsg }}</p>

      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200 text-xs text-gray-500">
            <th class="text-left pb-2 pr-2 w-24">借/貸</th>
            <th class="text-left pb-2 pr-2">勘定科目</th>
            <th class="text-right pb-2 pr-2 w-40">金額</th>
            <th class="text-left pb-2 w-1/3">摘要</th>
            <th class="w-6" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="(e, i) in form.entries" :key="i" class="border-b border-gray-100">
            <!-- 借方/貸方セレクト（借方: 青背景、貸方: 赤背景） -->
            <td class="py-1.5 pr-2">
              <select v-model="e.side" class="input text-xs"
                :class="e.side === 'debit' ? 'bg-blue-50' : 'bg-red-50'">
                <option value="debit">借方</option>
                <option value="credit">貸方</option>
              </select>
            </td>
            <!-- 勘定科目（category でグループ化） -->
            <td class="py-1.5 pr-2">
              <select v-model="e.account_item_id" class="input text-xs">
                <option value="">選択</option>
                <optgroup v-for="g in grouped" :key="g.label" :label="g.label">
                  <option v-for="a in g.items" :key="a.id" :value="a.id">
                    {{ a.code }} {{ a.name }}
                  </option>
                </optgroup>
              </select>
            </td>
            <td class="py-1.5 pr-2">
              <input v-model.number="e.amount" type="number" class="input text-right" min="1" />
            </td>
            <td class="py-1.5 pr-2">
              <input v-model="e.description" type="text" class="input text-xs" placeholder="任意" />
            </td>
            <!-- 最低2行を保持するため、2行以下では削除ボタンを非表示 -->
            <td class="py-1.5 text-center">
              <button type="button" @click="form.entries.splice(i, 1)"
                v-if="form.entries.length > 2" class="text-red-400 text-xs">✕</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- 貸借合計サマリー -->
      <div class="mt-4 border-t pt-4 flex justify-end gap-8 text-sm">
        <div class="text-center">
          <p class="text-gray-500 text-xs">借方合計</p>
          <p class="font-bold text-blue-700">{{ fmt(debitTotal) }}</p>
        </div>
        <div class="text-center">
          <p class="text-gray-500 text-xs">貸方合計</p>
          <p class="font-bold text-red-700">{{ fmt(creditTotal) }}</p>
        </div>
        <div class="text-center">
          <p class="text-gray-500 text-xs">差額</p>
          <!-- 一致すれば緑の ✓、不一致なら差額を橙色で表示 -->
          <p class="font-bold" :class="balanced ? 'text-green-600' : 'text-orange-600'">
            {{ balanced ? '✓ 一致' : fmt(Math.abs(debitTotal - creditTotal)) }}
          </p>
        </div>
      </div>
    </div>

    <!-- フォームアクション -->
    <div class="flex gap-3">
      <!-- 貸借不一致の場合は登録ボタンを無効化 -->
      <button @click="submit" :disabled="saving || !balanced" class="btn-primary disabled:opacity-50">
        {{ id ? '更新する' : '登録する' }}
      </button>
      <a href="#/journals" class="btn-secondary">キャンセル</a>
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

const saving   = ref(false)
const errMsg   = ref('')    // APIエラーメッセージ
const accounts = ref([])   // 全勘定科目

const form = ref({
  journal_date: new Date().toISOString().slice(0, 10),
  description:  '',
  entries: [
    { side: 'debit',  account_item_id: '', amount: 0, description: '' }, // 借方
    { side: 'credit', account_item_id: '', amount: 0, description: '' }, // 貸方
  ],
})

/** category の日本語ラベルマッピング */
const catLabels = { asset: '資産', liability: '負債', equity: '純資産', revenue: '収益', expense: '費用' }

/** 勘定科目を category でグループ化する */
const grouped = computed(() => {
  const g = {}
  for (const a of accounts.value) {
    if (!g[a.category]) g[a.category] = []
    g[a.category].push(a)
  }
  return Object.entries(g).map(([c, items]) => ({ label: catLabels[c] ?? c, items }))
})

/** 借方合計 */
const debitTotal  = computed(() =>
  form.value.entries.filter(e => e.side === 'debit').reduce((s, e) => s + (e.amount || 0), 0)
)
/** 貸方合計 */
const creditTotal = computed(() =>
  form.value.entries.filter(e => e.side === 'credit').reduce((s, e) => s + (e.amount || 0), 0)
)
/** 貸借バランスチェック（借方 > 0 かつ 借方 === 貸方） */
const balanced = computed(() => debitTotal.value > 0 && debitTotal.value === creditTotal.value)

function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

onMounted(async () => {
  const r = await api.get('/account-items')
  accounts.value = r.data
  if (id) {
    const j = await api.get(`/journals/${id}`)
    Object.assign(form.value, j.data)
  }
})

async function submit() {
  saving.value = true
  errMsg.value = ''
  try {
    if (id) {
      await api.put(`/journals/${id}`, form.value)
      flash.success('仕訳を更新しました。')
    } else {
      await api.post('/journals', form.value)
      flash.success('仕訳を登録しました。')
    }
    router.push('#/journals')
  } catch (e) {
    // バランスエラーやバリデーションエラーを表示
    errMsg.value = e.response?.data?.errors?.entries?.[0] ?? 'エラーが発生しました。'
  } finally {
    saving.value = false
  }
}
</script>
