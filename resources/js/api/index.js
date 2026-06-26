/**
 * Axios API クライアント
 *
 * - baseURL: /api
 * - withCredentials: true  ← セッション Cookie を自動送信
 * - リクエストインターセプター: CSRF トークンを自動付与
 * - レスポンスインターセプター: 401 時にログインページへリダイレクト
 */
import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  },
  withCredentials: true, // セッション Cookie を毎回送信（認証維持）
})

// ── リクエストインターセプター ─────────────────────────────────
api.interceptors.request.use(config => {
  // <meta name="csrf-token"> から CSRF トークンを取得して付与
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  if (token) config.headers['X-CSRF-TOKEN'] = token
  return config
})

// ── レスポンスインターセプター ─────────────────────────────────
api.interceptors.response.use(
  res => res, // 成功: そのまま返す
  err => {
    // 401 Unauthorized → セッション切れ → ログインページへ強制遷移
    if (err.response?.status === 401) {
      window.location.hash = '/login'
    }
    return Promise.reject(err)
  }
)

export default api
