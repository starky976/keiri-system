<template>
  <div>
    <!-- ローディング中 -->
    <div v-if="!auth.state.checked" class="min-h-screen flex items-center justify-center bg-gray-50">
      <p class="text-gray-400 text-sm">読み込み中...</p>
    </div>

    <!-- 認証済み → アプリレイアウト -->
    <AppLayout v-else-if="auth.state.user && currentRoute?.auth !== false" :title="pageTitle">
      <component :is="currentComponent" v-if="currentComponent" />
      <div v-else class="text-center py-20 text-gray-400">ページが見つかりません</div>
    </AppLayout>

    <!-- 未認証 → ログインページ -->
    <component :is="currentComponent" v-else-if="currentComponent" />
    <div v-else class="min-h-screen flex items-center justify-center">
      <p class="text-gray-400">ページが見つかりません</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import AppLayout from './components/AppLayout.vue'
import { router } from './router/index.js'
import { useAuth } from './store/auth.js'

const auth = useAuth()

onMounted(async () => {
  await auth.check()
  // 未認証かつ認証必要ページ → ログインへ
  if (!auth.state.user && currentRoute.value?.auth !== false) {
    router.replace('/login')
  }
  // 認証済みかつログインページ → ダッシュボードへ
  if (auth.state.user && router.currentHash.value === '/login') {
    router.replace('/')
  }
})

// 認証状態変化を監視
watch(() => auth.state.user, (user) => {
  if (!user && currentRoute.value?.auth !== false) {
    router.replace('/login')
  }
})

const matched = computed(() => router.match.value)
const currentRoute = computed(() => matched.value?.route)
const currentComponent = computed(() => currentRoute.value?.component ?? null)

const titleMap = {
  '/': 'ダッシュボード', '/dashboard': 'ダッシュボード',
  '/clients': '取引先管理', '/clients/create': '取引先登録',
  '/sales': '売上管理', '/sales/create': '売上登録',
  '/invoices': '請求書管理', '/invoices/create': '請求書作成',
  '/receipts': '入金管理', '/receipts/create': '入金登録',
  '/payments': '支払管理', '/payments/create': '支払登録',
  '/journals': '仕訳入力', '/journals/create': '仕訳入力',
  '/ledger': '総勘定元帳',
  '/profit-loss': '損益計算書',
  '/balance-sheet': '貸借対照表',
  '/expenses': '経費精算', '/expenses/create': '経費申請',
  '/login': 'ログイン',
}

const pageTitle = computed(() => {
  const path = router.currentHash.value
  if (titleMap[path]) return titleMap[path]
  if (path.includes('/edit')) return '編集'
  if (path.startsWith('/ledger/')) return '勘定元帳明細'
  if (path.startsWith('/expenses/')) return '経費詳細'
  return '経理基幹システム'
})
</script>
