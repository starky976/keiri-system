import { reactive, readonly } from 'vue'
import api from '../api/index.js'

const state = reactive({
  user: null,
  checked: false,
})

export function useAuth() {
  async function check() {
    try {
      const res = await api.get('/user')
      state.user = res.data.user
    } catch {
      state.user = null
    } finally {
      state.checked = true
    }
  }

  async function login(email, password, remember = false) {
    const res = await api.post('/login', { email, password, remember })
    state.user = res.data.user
    return state.user
  }

  async function logout() {
    await api.post('/logout')
    state.user = null
    window.location.hash = '/login'
  }

  return {
    state: readonly(state),
    check,
    login,
    logout,
  }
}
