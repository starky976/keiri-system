<template>
  <div>
    <!--
      認証確認中（/api/user のレスポンス待ち）
      checked が false の間はローディング画面を表示する
    -->
    <div v-if="!auth.state.checked" class="min-h-screen flex items-center justify-center bg-gray-50">
      <p class="text-gray-400 text-sm">読み込み中...</p>
    </div>

    <!--
      認証済み、かつ認証必須ルート → AppLayout でラップしてページを表示
    -->
    <AppLayout v-else-if="auth.state.user && currentRoute?.auth !== false" :title="pageTitle">
      <component :is="currentComponent" v-if="currentComponent" />
      <div v-else class="text-center py-20 text-gray-400">ページが見つかりません</div>
    </AppLayout>

    <!--
      未認証、または auth: false のルート（ログインページなど）
      レイアウトなしでコンポーネントを直接表示する
    -->
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

/**
 * 初回マウント時に認証チェックを実行し、必要に応じてリダイレクトする。
 *  - 未認証 + 認証必須ルート → /login へリダイレクト
 *  - 認証済み + /login アクセス → / (ダッシュボード) へリダイレクト
 */
onMounted(async () => {
  await auth.check()

  if (!auth.state.user && currentRoute.value?.auth !== false) {
    router.replace('/login')
  }
  if (auth.state.user && router.currentHash.value === '/login') {
    router.replace('/')
  }
})

/**
 * ユーザー状態の変化を監視する。
 * ログアウト後（user が null になった）に認証必須ルートにいる場合、
 * 自動的にログインページへリダイレクトする。
 */
watch(() => auth.state.user, (user) => {
  if (!user && currentRoute.value?.auth !== false) {
    router.replace('/login')
  }
})

// ── 現在のルート情報 ──────────────────────────────────────────
const matched        = computed(() => router.match.value)
const currentRoute   = computed(() => matched.value?.route)
const currentComponent = computed(() => currentRoute.value?.component ?? null)

/**
 * ページタイトルマッピング
 * ヘッダー（AppLayout の <h1>）に表示するタイトルを返す
 */
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

/** 現在のパスに応じたページタイトルを返す computed */
const pageTitle = computed(() => {
  const path = router.currentHash.value
  if (titleMap[path]) return titleMap[path]
  if (path.includes('/edit'))        return '編集'
  if (path.startsWith('/ledger/'))   return '勘定元帳明細'
  if (path.startsWith('/expenses/')) return '経費詳細'
  return '経理基幹システム'
})
</script>
