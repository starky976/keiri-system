import { reactive } from 'vue'

const state = reactive({ message: '', type: 'success' })

export function useFlash() {
  function success(msg) { state.message = msg; state.type = 'success'; setTimeout(() => { state.message = '' }, 4000) }
  function error(msg)   { state.message = msg; state.type = 'error';   setTimeout(() => { state.message = '' }, 5000) }
  return { message: state.message, type: state.type, success, error, ...state }
}

// グローバルアクセス用
export const flash = { success: (m) => useFlash().success(m), error: (m) => useFlash().error(m) }
