/**
 * フラッシュメッセージストア
 *
 * API 操作の成功・失敗メッセージを一時表示するための軽量ストア。
 * success: 4秒後, error: 5秒後に自動クリアされる。
 *
 * 使い方:
 *  const flash = useFlash()
 *  flash.success('保存しました。')
 *  flash.error('エラーが発生しました。')
 */
import { reactive } from 'vue'

/** フラッシュメッセージの状態（グローバルシングルトン） */
const state = reactive({
  message: '', // 表示するメッセージ（空文字 = 非表示）
  type: 'success', // 'success' | 'error'
})

/**
 * useFlash コンポーザブル
 * @returns {{ message, type, success, error }}
 */
export function useFlash() {
  /** 成功メッセージを4秒間表示する */
  function success(msg) {
    state.message = msg
    state.type = 'success'
    setTimeout(() => { state.message = '' }, 4000)
  }

  /** エラーメッセージを5秒間表示する */
  function error(msg) {
    state.message = msg
    state.type = 'error'
    setTimeout(() => { state.message = '' }, 5000)
  }

  // state のプロパティをスプレッドして template から直接 flash.message 参照可能にする
  return { message: state.message, type: state.type, success, error, ...state }
}

/**
 * コンポーザブル外（コントローラー・util 等）からの呼び出し用ショートカット
 * @example  flash.success('削除しました。')
 */
export const flash = {
  success: (m) => useFlash().success(m),
  error:   (m) => useFlash().error(m),
}
