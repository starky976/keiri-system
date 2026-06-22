import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
  withCredentials: true,
})

// CSRFトークンを自動付与
api.interceptors.request.use(config => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  if (token) config.headers['X-CSRF-TOKEN'] = token
  return config
})

// 401 → ログインページへリダイレクト
api.interceptors.response.use(
  res => res,
  err => {
    if (err.response?.status === 401) {
      window.location.hash = '/login'
    }
    return Promise.reject(err)
  }
)

export default api
