# 経理基幹システム

中小企業（サービス業）向けの経理・財務管理 Web アプリケーションです。

## 技術スタック

| レイヤー | 技術 |
|---|---|
| バックエンド | Laravel 11 (API / セッション認証) |
| フロントエンド | Vue 3.x (SPA・Inertia なし) |
| スタイリング | Tailwind CSS v4 (`@tailwindcss/vite`) |
| HTTP クライアント | Axios (CSRF トークン自動付与) |
| ビルドツール | Vite 6 + `laravel-vite-plugin` |
| データベース | MySQL / SQLite |

## 機能一覧

1. **取引先管理** — 得意先・仕入先の登録、検索、CRUD
2. **売上管理** — 売上伝票の作成・明細管理、売上番号自動採番
3. **請求書管理** — 請求書の作成・送付ステータス管理
4. **入金管理** — 入金記録、請求書への消込
5. **支払管理** — 支払伝票の記録・管理
6. **仕訳帳** — 複式簿記による仕訳入力（借方・貸方バランス検証）
7. **総勘定元帳** — 勘定科目別の残高・明細照会
8. **損益計算書（P/L）** — 期間指定での収益・費用集計
9. **貸借対照表（B/S）** — 指定日時点の資産・負債・純資産
10. **経費精算** — 経費申請・承認ワークフロー（申請 → 承認 / 却下 → 支払済）

## セットアップ

### 必要環境

- PHP 8.2 以上
- Composer
- Node.js 20 以上 / npm
- MySQL 8 または SQLite

### インストール手順

```bash
# 1. 依存パッケージのインストール
composer install
npm install

# 2. 環境ファイルの設定
cp .env.example .env
php artisan key:generate

# 3. データベース設定（.env を編集）
#    DB_CONNECTION=mysql
#    DB_DATABASE=keiri_system
#    DB_USERNAME=root
#    DB_PASSWORD=

# 4. マイグレーション & シードデータ投入
php artisan migrate --seed

# 5. フロントエンドビルド（開発時）
npm run dev

# 6. Laravel サーバー起動
php artisan serve
```

ブラウザで `http://localhost:8000` を開いてください。

### デフォルトログイン情報

| 項目 | 値 |
|---|---|
| メールアドレス | admin@example.com |
| パスワード | password |

## ディレクトリ構成（主要部分）

```
keiri-system/
├── app/
│   ├── Http/Controllers/Api/   # API コントローラー（JSON レスポンス）
│   └── Models/                 # Eloquent モデル
├── database/
│   ├── migrations/             # テーブル定義
│   └── seeders/                # 初期データ（管理者・勘定科目）
├── resources/
│   ├── css/app.css             # Tailwind v4 エントリポイント
│   ├── js/
│   │   ├── app.js              # Vue アプリ起動
│   │   ├── App.vue             # ルートコンポーネント
│   │   ├── api/index.js        # Axios インスタンス（CSRF 自動付与）
│   │   ├── router/index.js     # ハッシュベースのカスタムルーター
│   │   ├── store/              # auth / flash ストア（Vue reactive）
│   │   ├── components/         # 共通コンポーネント
│   │   └── pages/              # 機能別ページコンポーネント
│   └── views/app.blade.php     # SPA エントリ HTML
└── routes/web.php              # /api/* ルート + SPA フォールバック
```

## アーキテクチャ概要

- **SPA 構成**: Inertia.js を使わず、Laravel はすべて JSON API として動作。Vue 3 が `/` 以下を SPA として管理。
- **認証**: Laravel セッション認証。`/api/login` でログイン後、`auth` ミドルウェアで保護。
- **CSRF**: `<meta name="csrf-token">` から取得し、Axios リクエスト毎に `X-CSRF-TOKEN` ヘッダーへ自動付与。
- **ルーティング**: `vue-router` を使わず、ハッシュ (`#/path`) ベースのカスタムルーターを実装。動的セグメント (`:id`) に対応。
- **Tailwind v4**: `postcss.config.js` での設定は不要。`@tailwindcss/vite` プラグインが処理を担う。

## 注意事項

- `tailwind.config.js` は v3 用のファイルが残っていますが v4 では使用されません（削除可）。
- `postcss.config.js` は空のままにしてください（`tailwindcss: {}` を追加すると v3 との競合が発生します）。
