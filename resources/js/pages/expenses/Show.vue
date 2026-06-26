<!--
  経費 詳細・承認ページ

  経費申請の詳細を表示し、承認者が承認・却下アクションを実行できる。
  ステータスに応じて表示するアクションボタンが変わる:
    - pending → 承認ボタン + 却下ボタン
    - approved/rejected → アクションなし（閲覧のみ）

  却下時は却下理由入力フォームをインラインで表示する。
-->
<template>
  <div class="max-w-2xl" v-if="exp">
    <div class="bg-white rounded-lg border shadow-sm p-6 space-y-4">

      <!-- ヘッダー: 番号・件名・ステータス -->
      <div class="flex justify-between items-start">
        <div>
          <p class="font-mono text-xs text-gray-500">{{ exp.expense_number }}</p>
          <h2 class="text-lg font-bold text-gray-800 mt-1">{{ exp.title }}</h2>
        </div>
        <StatusBadge :status="exp.status" />
      </div>

      <!-- 申請情報サマリー -->
      <div class="grid grid-cols-3 gap-4 text-sm">
        <div>
          <p class="text-gray-500 text-xs">申請者</p>
          <p>{{ exp.user?.name }}</p>
        </div>
        <div>
          <p class="text-gray-500 text-xs">申請日</p>
          <p>{{ exp.applied_date }}</p>
        </div>
        <div>
          <p class="text-gray-500 text-xs">合計金額</p>
          <p class="font-bold text-lg">{{ fmt(exp.total_amount) }}</p>
        </div>
      </div>

      <!-- 却下理由（却下済みの場合のみ表示） -->
      <div v-if="exp.rejection_reason"
        class="bg-red-50 border border-red-200 rounded p-3 text-sm text-red-700">
        <strong>却下理由:</strong> {{ exp.rejection_reason }}
      </div>

      <!-- 明細テーブル -->
      <table class="w-full text-sm border-t">
        <thead>
          <tr class="text-xs text-gray-500">
            <th class="text-left py-2 pr-2">日付</th>
            <th class="text-left py-2 pr-2">内容</th>
            <th class="text-left py-2 pr-2">科目</th>
            <th class="text-right py-2">金額</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="item in exp.items" :key="item.id">
            <td class="py-2 pr-2">{{ item.item_date }}</td>
            <td class="py-2 pr-2">{{ item.description }}</td>
            <td class="py-2 pr-2 text-xs text-gray-500">{{ item.account_item?.name }}</td>
            <td class="py-2 text-right font-medium">{{ fmt(item.amount) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- アクションボタン -->
      <div class="flex gap-3 pt-2">
        <!-- 申請中のみ承認・却下ボタンを表示 -->
        <button v-if="exp.status === 'pending'" @click="approve"
          class="btn-primary bg-green-600 hover:bg-green-700">承認する</button>
        <button v-if="exp.status === 'pending'" @click="showReject = true"
          class="btn-danger">却下する</button>
        <a :href="`#/expenses/${exp.id}/edit`" class="btn-secondary">編集</a>
        <a href="#/expenses" class="btn-secondary">一覧へ戻る</a>
      </div>

      <!-- 却下理由入力フォーム（却下ボタンクリック時にインライン表示） -->
      <div v-if="showReject" class="border-t pt-4">
        <label class="label">却下理由 *</label>
        <textarea v-model="reason" class="input mb-3" rows="3"
          placeholder="却下理由を入力してください" />
        <div class="flex gap-2">
          <button @click="reject" class="btn-danger">却下する</button>
          <button @click="showReject = false" class="btn-secondary">キャンセル</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import StatusBadge from '../../components/StatusBadge.vue'
import api         from '../../api/index.js'
import { router, useRoute } from '../../router/index.js'
import { useFlash } from '../../store/flash.js'

const route = useRoute()
const id    = route.params.value.id
const flash = useFlash()

const exp        = ref(null)  // 経費詳細データ
const showReject = ref(false) // 却下フォームの表示フラグ
const reason     = ref('')    // 却下理由の入力値

onMounted(async () => {
  const res = await api.get(`/expenses/${id}`)
  exp.value = res.data
})

/**
 * 経費申請を承認する
 * 承認後は画面をリロードして最新状態を表示する。
 */
async function approve() {
  if (!confirm('承認しますか？')) return
  await api.post(`/expenses/${id}/approve`)
  flash.success('承認しました。')
  // 詳細を再取得して表示を更新
  const res = await api.get(`/expenses/${id}`)
  exp.value = res.data
}

/**
 * 経費申請を却下する
 * 却下理由が未入力の場合はアラートを表示して処理を中断する。
 */
async function reject() {
  if (!reason.value.trim()) { alert('却下理由を入力してください'); return }
  await api.post(`/expenses/${id}/reject`, { reason: reason.value })
  flash.success('却下しました。')
  showReject.value = false
  // 詳細を再取得して表示を更新
  const res = await api.get(`/expenses/${id}`)
  exp.value = res.data
}

function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}
</script>
