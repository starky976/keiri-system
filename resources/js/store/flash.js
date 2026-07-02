/**
 * フラッシュメッセージストア
 *
 * API 操作の成功・失敗メッセージを一時表示するための軽量ストア。
 * success: 4秒後に自動クリア。error: 手動で ✕ を押すまで表示し続ける。
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

  /** エラーメッセージを表示する（自動消去しない。✕ ボタンで手動クリア） */
  function error(msg) {
    state.message = msg
    state.type = 'error'
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
