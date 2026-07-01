<!--
  貸借対照表（B/S）ページ

  基準日時点の資産・負債・純資産の残高を表示する。
  資産合計 === 負債 + 純資産 合計 の場合に「貸借バランス一致」と表示する。
  デフォルト基準日は今日。
-->
<template>
  <div>

    <!-- エラー表示 -->
    <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
      ⚠ {{ error }}
    </div>
    <!-- 基準日選択フォーム -->
    <div class="bg-white rounded-lg border shadow-sm p-4 mb-6 flex gap-3 items-center">
      <label class="text-sm font-medium text-gray-700">基準日</label>
      <input v-model="asOf" type="date" class="input w-40" />
      <button @click="load" class="btn-primary">表示</button>
      <span class="ml-auto text-sm text-gray-500">{{ asOf }} 時点</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- 資産の部 -->
      <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
        <div class="bg-green-50 px-6 py-3 border-b">
          <h2 class="text-sm font-semibold text-green-800">資産の部</h2>
        </div>
        <table class="w-full text-sm">
          <tbody class="divide-y divide-gray-100">
            <tr v-for="r in data.assets" :key="r.code" class="hover:bg-gray-50">
              <td class="td">{{ r.name }}</td>
              <td class="td text-right font-medium">{{ fmt(r.amount) }}</td>
            </tr>
            <tr v-if="!data.assets?.length">
              <td colspan="2" class="td text-center text-gray-400">データなし</td>
            </tr>
          </tbody>
          <tfoot class="bg-green-50">
            <tr>
              <td class="td font-bold text-green-800">資産合計</td>
              <td class="td text-right font-bold text-green-800 text-base">{{ fmt(data.totalAssets) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- 負債・純資産の部（右カラム） -->
      <div class="space-y-4">

        <!-- 負債の部 -->
        <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
          <div class="bg-red-50 px-6 py-3 border-b">
            <h2 class="text-sm font-semibold text-red-800">負債の部</h2>
          </div>
          <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
              <tr v-for="r in data.liabilities" :key="r.code" class="hover:bg-gray-50">
                <td class="td">{{ r.name }}</td>
                <td class="td text-right font-medium">{{ fmt(r.amount) }}</td>
              </tr>
              <tr v-if="!data.liabilities?.length">
                <td colspan="2" class="td text-center text-gray-400">データなし</td>
              </tr>
            </tbody>
            <tfoot class="bg-red-50">
              <tr>
                <td class="td font-bold text-red-800">負債合計</td>
                <td class="td text-right font-bold text-red-800">{{ fmt(data.totalLiabilities) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- 純資産の部 -->
        <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
          <div class="bg-purple-50 px-6 py-3 border-b">
            <h2 class="text-sm font-semibold text-purple-800">純資産の部</h2>
          </div>
          <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
              <tr v-for="r in data.equity" :key="r.code" class="hover:bg-gray-50">
                <td class="td">{{ r.name }}</td>
                <td class="td text-right font-medium">{{ fmt(r.amount) }}</td>
              </tr>
              <tr v-if="!data.equity?.length">
                <td colspan="2" class="td text-center text-gray-400">データなし</td>
              </tr>
            </tbody>
            <tfoot class="bg-purple-50">
              <tr>
                <td class="td font-bold text-purple-800">純資産合計</td>
                <td class="td text-right font-bold text-purple-800">{{ fmt(data.totalEquity) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- 負債・純資産の合計 -->
        <div class="bg-white rounded-lg border shadow-sm p-4 flex justify-between items-center">
          <span class="font-bold text-gray-700">負債・純資産合計</span>
          <span class="font-bold text-gray-900">
            {{ fmt((data.totalLiabilities ?? 0) + (data.totalEquity ?? 0)) }}
          </span>
        </div>
      </div>
    </div>

    <!-- 貸借バランスチェック結果 -->
    <p class="mt-3 text-center text-sm" :class="balanced ? 'text-green-600' : 'text-red-600'">
      {{ balanced
        ? '✓ 貸借バランス：一致'
        : `⚠ 差額: ${fmt(Math.abs((data.totalAssets ?? 0) - (data.totalLiabilities ?? 0) - (data.totalEquity ?? 0)))}` }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import LoadingSpinner from '../../components/LoadingSpinner.vue'
import { useAsync }    from '../../composables/useAsync.js'
import api from '../../api/index.js'

const { loading, error, execute } = useAsync()

const data = ref({}) // B/S レスポンス全体
/** 基準日（デフォルト: 今日） */
const asOf = ref(new Date().toISOString().slice(0, 10))

/**
 * 貸借バランスチェック
 * 資産合計 === 負債 + 純資産 の場合に true を返す。
 * 浮動小数点の誤差を許容するため差額が 1 円未満なら一致とみなす。
 */
const balanced = computed(() =>
  Math.abs((data.value.totalAssets ?? 0) - (data.value.totalLiabilities ?? 0) - (data.value.totalEquity ?? 0)) < 1
)

async function load() {
  await execute(async () => {
    const res  = await api.get('/balance-sheet', { params: { as_of: asOf.value } })
    data.value = res.data
  })
}

function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

onMounted(() => load())
</script>
