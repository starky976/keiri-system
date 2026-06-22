import { ref, computed, markRaw } from 'vue'

// ページコンポーネント（遅延インポートで必要なときだけ読み込む）
import LoginPage from '../pages/Login.vue'
import DashboardPage from '../pages/Dashboard.vue'
import ClientsIndex from '../pages/clients/Index.vue'
import ClientsForm from '../pages/clients/Form.vue'
import SalesIndex from '../pages/sales/Index.vue'
import SalesForm from '../pages/sales/Form.vue'
import InvoicesIndex from '../pages/invoices/Index.vue'
import InvoicesForm from '../pages/invoices/Form.vue'
import ReceiptsIndex from '../pages/receipts/Index.vue'
import ReceiptsForm from '../pages/receipts/Form.vue'
import PaymentsIndex from '../pages/payments/Index.vue'
import PaymentsForm from '../pages/payments/Form.vue'
import JournalsIndex from '../pages/journals/Index.vue'
import JournalsForm from '../pages/journals/Form.vue'
import LedgerIndex from '../pages/ledger/Index.vue'
import LedgerShow from '../pages/ledger/Show.vue'
import PLIndex from '../pages/pl/Index.vue'
import BSIndex from '../pages/bs/Index.vue'
import ExpensesIndex from '../pages/expenses/Index.vue'
import ExpensesForm from '../pages/expenses/Form.vue'
import ExpensesShow from '../pages/expenses/Show.vue'

// ルート定義
const routes = [
  { path: '/login',                  component: markRaw(LoginPage),     auth: false },
  { path: '/',                       component: markRaw(DashboardPage),  auth: true },
  { path: '/dashboard',              component: markRaw(DashboardPage),  auth: true },
  { path: '/clients',                component: markRaw(ClientsIndex),   auth: true },
  { path: '/clients/create',         component: markRaw(ClientsForm),    auth: true },
  { path: '/clients/:id/edit',       component: markRaw(ClientsForm),    auth: true },
  { path: '/sales',                  component: markRaw(SalesIndex),     auth: true },
  { path: '/sales/create',           component: markRaw(SalesForm),      auth: true },
  { path: '/sales/:id/edit',         component: markRaw(SalesForm),      auth: true },
  { path: '/invoices',               component: markRaw(InvoicesIndex),  auth: true },
  { path: '/invoices/create',        component: markRaw(InvoicesForm),   auth: true },
  { path: '/invoices/:id/edit',      component: markRaw(InvoicesForm),   auth: true },
  { path: '/receipts',               component: markRaw(ReceiptsIndex),  auth: true },
  { path: '/receipts/create',        component: markRaw(ReceiptsForm),   auth: true },
  { path: '/receipts/:id/edit',      component: markRaw(ReceiptsForm),   auth: true },
  { path: '/payments',               component: markRaw(PaymentsIndex),  auth: true },
  { path: '/payments/create',        component: markRaw(PaymentsForm),   auth: true },
  { path: '/payments/:id/edit',      component: markRaw(PaymentsForm),   auth: true },
  { path: '/journals',               component: markRaw(JournalsIndex),  auth: true },
  { path: '/journals/create',        component: markRaw(JournalsForm),   auth: true },
  { path: '/journals/:id/edit',      component: markRaw(JournalsForm),   auth: true },
  { path: '/ledger',                 component: markRaw(LedgerIndex),    auth: true },
  { path: '/ledger/:code',           component: markRaw(LedgerShow),     auth: true },
  { path: '/profit-loss',            component: markRaw(PLIndex),        auth: true },
  { path: '/balance-sheet',          component: markRaw(BSIndex),        auth: true },
  { path: '/expenses',               component: markRaw(ExpensesIndex),  auth: true },
  { path: '/expenses/create',        component: markRaw(ExpensesForm),   auth: true },
  { path: '/expenses/:id',           component: markRaw(ExpensesShow),   auth: true },
  { path: '/expenses/:id/edit',      component: markRaw(ExpensesForm),   auth: true },
]

// 現在のハッシュパスを追跡
const currentHash = ref(getPath())

function getPath() {
  return window.location.hash.slice(1) || '/'
}

window.addEventListener('hashchange', () => {
  currentHash.value = getPath()
})

// パスマッチング（動的セグメント対応）
function matchRoute(path) {
  const [cleanPath, queryStr] = path.split('?')
  const query = {}
  if (queryStr) {
    new URLSearchParams(queryStr).forEach((v, k) => { query[k] = v })
  }

  for (const route of routes) {
    const routeParts = route.path.split('/')
    const pathParts = cleanPath.split('/')
    if (routeParts.length !== pathParts.length) continue

    const params = {}
    let match = true
    for (let i = 0; i < routeParts.length; i++) {
      if (routeParts[i].startsWith(':')) {
        params[routeParts[i].slice(1)] = pathParts[i]
      } else if (routeParts[i] !== pathParts[i]) {
        match = false; break
      }
    }
    if (match) return { route, params, query }
  }
  return null
}

export const router = {
  currentHash,
  push(path) {
    window.location.hash = path
  },
  replace(path) {
    window.location.replace('#' + path)
  },
  back() {
    history.back()
  },
  match: computed(() => matchRoute(currentHash.value)),
}

export function useRoute() {
  return {
    params: computed(() => router.match.value?.params ?? {}),
    query:  computed(() => router.match.value?.query ?? {}),
    path:   currentHash,
  }
}
