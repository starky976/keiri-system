<!--
  取引先一覧ページ

  検索・種別フィルタリング・ページネーションを備えた取引先テーブルを表示する。
  削除は論理削除（SoftDelete）のため、テーブルからは消えるがDBには残る。
-->
<template>
  <div>
    <!-- 検索・フィルター行 -->
    <div class="flex justify-between items-center mb-4">
      <div class="flex gap-2">
        <!-- キーワード検索: 取引先名・コード -->
        <input v-model="search" @keyup.enter="load(1)" type="text"
          placeholder="取引先名・コード"
          class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-56" />

        <!-- 種別フィルター -->
        <select v-model="typeFilter" @change="load(1)"
          class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">すべて</option>
          <option value="customer">得意先</option>
          <option value="vendor">仕入先</option>
          <option value="both">両方</option>
        </select>

        <button @click="load(1)" class="btn-secondary">検索</button>
      </div>

      <!-- 新規登録ボタン -->
      <a href="#/clients/create" class="btn-primary">+ 新規登録</a>
    </div>

    <!-- 取引先テーブル -->
    <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="th">コード</th>
            <th class="th">取引先名</th>
            <th class="th">区分</th>
            <th class="th">電話</th>
            <th class="th">担当者</th>
            <th class="th">状態</th>
            <th class="th w-24">操作</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <!-- 読み込み中 -->
          <tr v-if="loading">
            <td colspan="7" class="td text-center text-gray-400">読み込み中...</td>
          </tr>
          <!-- データなし -->
          <tr v-else-if="!rows.length">
            <td colspan="7" class="td text-center text-gray-400">データがありません</td>
          </tr>
          <!-- データ行 -->
          <tr v-for="c in rows" :key="c.id" class="hover:bg-gray-50">
            <td class="td font-mono text-xs">{{ c.code }}</td>
            <td class="td">
              <a :href="`#/clients/${c.id}/edit`" class="text-blue-600 hover:underline font-medium">
                {{ c.name }}
              </a>
            </td>
            <td class="td"><StatusBadge :status="c.type" /></td>
            <td class="td">{{ c.phone || '-' }}</td>
            <td class="td">{{ c.contact_person || '-' }}</td>
            <td class="td">
              <!-- 有効/無効バッジ -->
              <span class="badge" :class="c.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                {{ c.is_active ? '有効' : '無効' }}
              </span>
            </td>
            <td class="td">
              <div class="flex gap-2">
                <a :href="`#/clients/${c.id}/edit`" class="text-blue-600 hover:underline text-xs">編集</a>
                <button @click="del(c)" class="text-red-600 hover:underline text-xs">削除</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ページネーション -->
    <Pagination :data="pagination" @page="load" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import StatusBadge from '../../components/StatusBadge.vue'
import Pagination  from '../../components/Pagination.vue'
import api         from '../../api/index.js'
import { useFlash } from '../../store/flash.js'

/** テーブルデータ */
const rows       = ref([])
/** ページネーション情報 */
const pagination = ref({})
/** 検索キーワード */
const search     = ref('')
/** 種別フィルター（'customer' | 'vendor' | 'both' | ''） */
const typeFilter = ref('')
/** データ取得中フラグ */
const loading    = ref(false)
const flash      = useFlash()

/**
 * 取引先一覧を取得する
 * @param {number} page - 取得するページ番号
 */
async function load(page = 1) {
  loading.value = true
  const res = await api.get('/clients', {
    params: { search: search.value, type: typeFilter.value, page },
  })
  rows.value       = res.data.data
  pagination.value = res.data
  loading.value    = false
}

/**
 * 取引先を論理削除する
 * 削除前にブラウザ確認ダイアログを表示する。
 * @param {Object} c - 削除対象の取引先オブジェクト
 */
async function del(c) {
  if (!confirm(`「${c.name}」を削除しますか？`)) return
  await api.delete(`/clients/${c.id}`)
  flash.success('削除しました。')
  load() // リスト再取得
}

// マウント時に1ページ目を読み込む
onMounted(() => load())
</script>
