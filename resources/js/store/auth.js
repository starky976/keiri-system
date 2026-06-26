/**
 * 認証ストア
 *
 * Vue の reactive() で認証状態をグローバル管理する軽量ストア。
 * Pinia や Vuex は使わず、Composition API のみで実装している。
 *
 * state:
 *  - user    : ログイン中のユーザーオブジェクト (null = 未認証)
 *  - checked : /api/user チェック完了フラグ（初回ロード時に使用）
 */
import { reactive, readonly } from 'vue'
import api from '../api/index.js'

/** グローバル認証状態（シングルトン） */
const state = reactive({
  user: null,    // ログイン中ユーザー情報
  checked: false, // 認証確認済みフラグ
})

/**
 * useAuth コンポーザブル
 *
 * @returns {{ state, check, login, logout }}
 */
export function useAuth() {
  /**
   * 現在のセッションからユーザー情報を取得する
   * ページリロード時に App.vue の onMounted で1回だけ呼ぶ
   */
  async function check() {
    try {
      const res = await api.get('/user')
      state.user = res.data.user
    } catch {
      // 未認証 (401) の場合は null のままにする
      state.user = null
    } finally {
      // 成否に関わらず確認完了フラグを立てる
      state.checked = true
    }
  }

  /**
   * ログイン処理
   *
   * @param  {string}  email
   * @param  {string}  password
   * @param  {boolean} remember  - セッション延長フラグ
   * @returns  ログイン後のユーザーオブジェクト
   */
  async function login(email, password, remember = false) {
    const res = await api.post('/login', { email, password, remember })
    state.user = res.data.user
    return state.user
  }

  /**
   * ログアウト処理
   * サーバーセッションを破棄してログインページへ遷移する
   */
  async function logout() {
    await api.post('/logout')
    state.user = null
    window.location.hash = '/login'
  }

  return {
    state: readonly(state), // 外部からの直接変更を防ぐため readonly でラップ
    check,
    login,
    logout,
  }
}
