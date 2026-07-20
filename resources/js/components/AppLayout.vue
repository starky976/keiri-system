<!--
  AppLayout コンポーネント

  認証済みユーザー向けのメインレイアウト。
  左側に固定サイドバー、右側にヘッダー + メインコンテンツエリアを配置する。

  props:
    title - ヘッダー <h1> に表示するページタイトル

  slots:
    default - メインコンテンツ（各ページコンポーネント）
    actions - ヘッダー右側のアクションボタン群
-->
<template>
  <div class="min-h-screen flex">

    <!-- ═══════════════════════════════════════
         サイドバー（固定・幅 64 = 256px）
    ═══════════════════════════════════════ -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col fixed inset-y-0 left-0 z-30">

      <!-- ロゴ・システム名 -->
      <div class="h-16 flex items-center px-5 border-b border-gray-700">
        <span class="text-lg font-bold">📊 経理基幹システム</span>
      </div>

      <!-- ナビゲーションメニュー -->
      <nav class="flex-1 overflow-y-auto py-3 space-y-0.5">
        <NavItem href="#/" icon="🏠">ダッシュボード</NavItem>

        <!-- セクション: 売上・請求 -->
        <div class="px-4 pt-4 pb-1">
          <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">売上・請求</p>
        </div>
        <NavItem href="#/clients"  icon="🏢">取引先管理</NavItem>
        <NavItem href="#/sales"    icon="💹">売上管理</NavItem>
        <NavItem href="#/invoices" icon="📄">請求書管理</NavItem>
        <NavItem href="#/receipts" icon="💰">入金管理</NavItem>

        <!-- セクション: 支出・経費 -->
        <div class="px-4 pt-4 pb-1">
          <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">支出・経費</p>
        </div>
        <NavItem href="#/payments" icon="💳">支払管理</NavItem>
        <NavItem href="#/expenses" icon="🧾">経費精算</NavItem>

        <!-- セクション: 会計帳簿 -->
        <div class="px-4 pt-4 pb-1">
          <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">会計帳簿</p>
        </div>
        <NavItem href="#/journals"      icon="📝">仕訳入力</NavItem>
        <NavItem href="#/ledger"        icon="📚">総勘定元帳</NavItem>
        <NavItem href="#/profit-loss"   icon="📈">損益計算書</NavItem>
        <NavItem href="#/balance-sheet" icon="⚖️">貸借対照表</NavItem>
        <!-- 追加5機能 -->
        <NavItem href="#/budgets"       icon="🎯">予算管理</NavItem>
        <NavItem href="#/assets"        icon="🏗️">固定資産</NavItem>
        <NavItem href="#/departments"   icon="🏬">部門管理</NavItem>
        <NavItem href="#/tax"           icon="🧮">消費税管理</NavItem>
        <NavItem href="#/documents"     icon="🖨️">帳票出力</NavItem>
        <!-- 機能16 -->
        <NavItem href="#/statements"    icon="📋">楽々明細CSV出力</NavItem>
      </nav>

      <!-- ユーザー情報・ログアウト -->
      <div class="border-t border-gray-700 p-4">
        <p class="text-sm font-medium text-white">{{ auth.state.user?.name }}</p>
        <p class="text-xs text-gray-400 mb-2">{{ auth.state.user?.email }}</p>
        <button @click="auth.logout()" class="text-xs text-gray-400 hover:text-white transition">
          ログアウト →
        </button>
      </div>
    </aside>

    <!-- ═══════════════════════════════════════
         メインエリア（サイドバー分を左マージン）
    ═══════════════════════════════════════ -->
    <div class="ml-64 flex flex-col flex-1">

      <!-- ヘッダー（固定） -->
      <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6 sticky top-0 z-20">
        <!-- ページタイトル -->
        <h1 class="text-base font-semibold text-gray-800">{{ title }}</h1>
        <!-- 右側スロット: 「新規登録」ボタンなど各ページが注入 -->
        <div class="ml-auto flex items-center gap-3">
          <slot name="actions" />
        </div>
      </header>

      <!--
        フラッシュメッセージ
        success: 緑背景, error: 赤背景
        ✕ ボタンで手動クリアも可能
      -->
      <div v-if="flash.message" class="mx-6 mt-4">
        <div
          :class="flash.type === 'error'
            ? 'bg-red-50 border-red-200 text-red-800'
            : 'bg-green-50 border-green-200 text-green-800'"
          class="border rounded-lg p-3 flex justify-between items-center text-sm">
          {{ flash.message }}
          <button @click="flash.message = ''" class="ml-4 opacity-60 hover:opacity-100">✕</button>
        </div>
      </div>

      <!-- メインコンテンツ（各ページがここに差し込まれる） -->
      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import NavItem from './NavItem.vue'
import { useAuth } from '../store/auth.js'
import { useFlash } from '../store/flash.js'

defineProps({ title: String })

const auth  = useAuth()   // 認証ストア（ユーザー名・ログアウト）
const flash = useFlash()  // フラッシュメッセージストア
</script>
