<!--
  Login ページ

  認証不要（auth: false）のログインフォーム。
  メールアドレス + パスワードで /api/login を呼び出し、
  成功時はダッシュボード（/）へリダイレクトする。
-->
<template>
  <div class="min-h-screen bg-gray-900 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-md">
      <!-- ロゴ・タイトル -->
      <div class="text-center mb-8">
        <div class="text-5xl mb-3">📊</div>
        <h1 class="text-2xl font-bold text-gray-800">経理基幹システム</h1>
        <p class="text-gray-500 text-sm mt-1">ログインしてください</p>
      </div>

      <!-- ログインフォーム -->
      <form @submit.prevent="submit" class="space-y-5">
        <!-- メールアドレス入力 -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
          <input v-model="form.email" type="email"
            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="admin@example.com" required />
        </div>

        <!-- パスワード入力 -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">パスワード</label>
          <input v-model="form.password" type="password"
            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            required />
        </div>

        <!-- エラーメッセージ -->
        <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>

        <!-- 送信ボタン（通信中は disabled） -->
        <button type="submit" :disabled="loading"
          class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-60">
          {{ loading ? 'ログイン中...' : 'ログイン' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuth } from '../store/auth.js'
import { router } from '../router/index.js'

const auth = useAuth()

/** フォームの入力値 */
const form    = ref({ email: '', password: '' })
/** APIエラーメッセージ */
const error   = ref('')
/** 通信中フラグ（二重送信防止） */
const loading = ref(false)

/**
 * ログインフォームの送信処理
 * 失敗時はサーバーから返ったメッセージをエラー表示する。
 */
async function submit() {
  error.value   = ''
  loading.value = true
  try {
    await auth.login(form.value.email, form.value.password)
    router.replace('/') // 成功 → ダッシュボードへ
  } catch (e) {
    // サーバーエラーメッセージを優先して表示
    error.value = e.response?.data?.message
      ?? e.response?.data?.errors?.email?.[0]
      ?? 'ログインに失敗しました。'
  } finally {
    loading.value = false
  }
}
</script>
