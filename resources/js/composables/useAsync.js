/**
 * useAsync コンポーザブル
 *
 * API 呼び出しを loading / error 状態とセットで管理する。
 * 各ページで同じ try/catch/finally を繰り返す代わりにこれを使う。
 *
 * 使い方:
 *   const { loading, error, execute } = useAsync()
 *   await execute(() => api.get('/clients'))
 */
import { ref } from 'vue'

export function useAsync() {
  /** API 呼び出し中は true */
  const loading = ref(false)
  /** エラーメッセージ。正常時は null */
  const error = ref(null)

  /**
   * 非同期関数を loading / error 管理しながら実行する
   * @param {Function} fn - 実行する非同期関数
   * @returns {Promise<*>} fn の戻り値
   */
  async function execute(fn) {
    loading.value = true
    error.value   = null
    try {
      return await fn()
    } catch (e) {
      // サーバーエラーメッセージがあれば優先して表示
      error.value = e.response?.data?.message ?? 'エラーが発生しました。時間をおいて再度お試しください。'
      throw e
    } finally {
      loading.value = false
    }
  }

  return { loading, error, execute }
}
