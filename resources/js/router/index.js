/**
 * ハッシュベースカスタムルーター
 *
 * vue-router を使わず、window.location.hash と Vue の reactive() だけで
 * クライアントサイドルーティングを実装している。
 *
 * 特徴:
 *  - ページコンポーネントは markRaw() でラップ（不要なリアクティブ変換を防止）
 *  - 動的セグメント (:id, :code) に対応
 *  - クエリパラメータ (?from=...&to=...) の解析に対応
 *  - auth: false のルートは未認証でアクセス可能
 */
import { ref, computed, markRaw } from 'vue'

// ── ページコンポーネントのインポート ──────────────────────────
import LoginPage      from '../pages/Login.vue'
import DashboardPage  from '../pages/Dashboard.vue'
import ClientsIndex   from '../pages/clients/Index.vue'
import ClientsForm    from '../pages/clients/Form.vue'
import SalesIndex     from '../pages/sales/Index.vue'
import SalesForm      from '../pages/sales/Form.vue'
import InvoicesIndex  from '../pages/invoices/Index.vue'
import InvoicesForm   from '../pages/invoices/Form.vue'
import ReceiptsIndex  from '../pages/receipts/Index.vue'
import ReceiptsForm   from '../pages/receipts/Form.vue'
import PaymentsIndex  from '../pages/payments/Index.vue'
import PaymentsForm   from '../pages/payments/Form.vue'
import JournalsIndex  from '../pages/journals/Index.vue'
import JournalsForm   from '../pages/journals/Form.vue'
import LedgerIndex    from '../pages/ledger/Index.vue'
import LedgerShow     from '../pages/ledger/Show.vue'
import PLIndex        from '../pages/pl/Index.vue'
import BSIndex        from '../pages/bs/Index.vue'
import ExpensesIndex  from '../pages/expenses/Index.vue'
import ExpensesForm   from '../pages/expenses/Form.vue'
import ExpensesShow   from '../pages/expenses/Show.vue'
// ── 追加5機能（機能11〜15）────────────────────────────────────
import BudgetsIndex   from '../pages/budgets/Index.vue'
import BudgetsForm    from '../pages/budgets/Form.vue'
import AssetsIndex    from '../pages/assets/Index.vue'
import AssetsForm     from '../pages/assets/Form.vue'
import AssetsShow     from '../pages/assets/Show.vue'
import DepartmentsIndex from '../pages/departments/Index.vue'
import TaxIndex       from '../pages/tax/Index.vue'
import DocumentsIndex from '../pages/documents/Index.vue'

/**
 * ルート定義テーブル
 * path: ハッシュパス（動的セグメントは :変数名）
 * component: 表示するページコンポーネント（markRaw でラップ済み）
 * auth: false の場合は未認証でもアクセス可能
 */
const routes = [
  { path: '/login',             component: markRaw(LoginPage),    auth: false },
  { path: '/',                  component: markRaw(DashboardPage), auth: true },
  { path: '/dashboard',         component: markRaw(DashboardPage), auth: true },
  { path: '/clients',           component: markRaw(ClientsIndex),  auth: true },
  { path: '/clients/create',    component: markRaw(ClientsForm),   auth: true },
  { path: '/clients/:id/edit',  component: markRaw(ClientsForm),   auth: true },
  { path: '/sales',             component: markRaw(SalesIndex),    auth: true },
  { path: '/sales/create',      component: markRaw(SalesForm),     auth: true },
  { path: '/sales/:id/edit',    component: markRaw(SalesForm),     auth: true },
  { path: '/invoices',          component: markRaw(InvoicesIndex), auth: true },
  { path: '/invoices/create',   component: markRaw(InvoicesForm),  auth: true },
  { path: '/invoices/:id/edit', component: markRaw(InvoicesForm),  auth: true },
  { path: '/receipts',          component: markRaw(ReceiptsIndex), auth: true },
  { path: '/receipts/create',   component: markRaw(ReceiptsForm),  auth: true },
  { path: '/receipts/:id/edit', component: markRaw(ReceiptsForm),  auth: true },
  { path: '/payments',          component: markRaw(PaymentsIndex), auth: true },
  { path: '/payments/create',   component: markRaw(PaymentsForm),  auth: true },
  { path: '/payments/:id/edit', component: markRaw(PaymentsForm),  auth: true },
  { path: '/journals',          component: markRaw(JournalsIndex), auth: true },
  { path: '/journals/create',   component: markRaw(JournalsForm),  auth: true },
  { path: '/journals/:id/edit', component: markRaw(JournalsForm),  auth: true },
  { path: '/ledger',            component: markRaw(LedgerIndex),   auth: true },
  { path: '/ledger/:code',      component: markRaw(LedgerShow),    auth: true },
  { path: '/profit-loss',       component: markRaw(PLIndex),       auth: true },
  { path: '/balance-sheet',     component: markRaw(BSIndex),       auth: true },
  { path: '/expenses',          component: markRaw(ExpensesIndex), auth: true },
  { path: '/expenses/create',   component: markRaw(ExpensesForm),  auth: true },
  { path: '/expenses/:id',      component: markRaw(ExpensesShow),  auth: true },
  { path: '/expenses/:id/edit', component: markRaw(ExpensesForm),  auth: true },
  // ── 追加5機能 ──────────────────────────────────────────────
  { path: '/budgets',             component: markRaw(BudgetsIndex),     auth: true },
  { path: '/budgets/create',      component: markRaw(BudgetsForm),      auth: true },
  { path: '/assets',              component: markRaw(AssetsIndex),      auth: true },
  { path: '/assets/create',       component: markRaw(AssetsForm),       auth: true },
  { path: '/assets/:id',          component: markRaw(AssetsShow),       auth: true },
  { path: '/assets/:id/edit',     component: markRaw(AssetsForm),       auth: true },
  { path: '/departments',         component: markRaw(DepartmentsIndex), auth: true },
  { path: '/tax',                 component: markRaw(TaxIndex),         auth: true },
  { path: '/documents',           component: markRaw(DocumentsIndex),   auth: true },
]

/** 現在のハッシュパス（リアクティブ）。hashchange イベントで自動更新される */
const currentHash = ref(getPath())

/** window.location.hash からパスを取得するユーティリティ */
function getPath() {
  return window.location.hash.slice(1) || '/'
}

// hashchange イベントを監視してリアクティブ値を同期
window.addEventListener('hashchange', () => {
  currentHash.value = getPath()
})

/**
 * パスマッチング
 *
 * 動的セグメント（:id など）を含むルートに対しても正確にマッチし、
 * パラメータ・クエリパラメータを抽出して返す。
 *
 * @param  {string}  path  - 現在のハッシュパス（クエリ付き可）
 * @returns {{ route, params, query } | null}
 */
function matchRoute(path) {
  // クエリ文字列を分離
  const [cleanPath, queryStr] = path.split('?')
  const query = {}
  if (queryStr) {
    new URLSearchParams(queryStr).forEach((v, k) => { query[k] = v })
  }

  for (const route of routes) {
    const routeParts = route.path.split('/')
    const pathParts  = cleanPath.split('/')
    if (routeParts.length !== pathParts.length) continue

    const params = {}
    let match = true
    for (let i = 0; i < routeParts.length; i++) {
      if (routeParts[i].startsWith(':')) {
        // 動的セグメント: パラメータとして抽出（例: :id → params.id = '5'）
        params[routeParts[i].slice(1)] = pathParts[i]
      } else if (routeParts[i] !== pathParts[i]) {
        match = false; break
      }
    }
    if (match) return { route, params, query }
  }
  return null // マッチするルートなし
}

/**
 * router オブジェクト
 *
 * App.vue からインポートして使用する。
 * match は computed で現在のハッシュに応じて自動更新される。
 */
export const router = {
  currentHash,

  /** プッシュナビゲーション（ブラウザ履歴に追加） */
  push(path) { window.location.hash = path },

  /** リプレースナビゲーション（履歴を置き換え） */
  replace(path) { window.location.replace('#' + path) },

  /** ブラウザの「戻る」 */
  back() { history.back() },

  /** 現在のルートマッチ結果（computed） */
  match: computed(() => matchRoute(currentHash.value)),
}

/**
 * useRoute コンポーザブル
 *
 * 各ページコンポーネントで現在のルートパラメータ・クエリを取得するために使う。
 *
 * @example
 *  const { params, query } = useRoute()
 *  const id = params.value.id  // URL の :id 部分
 */
export function useRoute() {
  return {
    params: computed(() => router.match.value?.params ?? {}),
    query:  computed(() => router.match.value?.query  ?? {}),
    path:   currentHash,
  }
}
