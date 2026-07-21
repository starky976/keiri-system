<!--
  楽々明細 CSV出力ページ（機能16）

  「楽々明細」サービスに取り込むための明細CSVを作成する。
  固定情報（会社名・住所・電話番号）は全行共通、明細行（取引日・品目・金額）は
  行追加/削除で複数登録できる。「CSVダウンロード」でShift-JISエンコードのCSVを出力する。

  ※ この機能はサーバーに保存せず、ブラウザ内だけで完結する
    （フォーム入力 → CSV変換 → ダウンロード の2ステップのみ）。
  ※ CSV生成・ダウンロード処理はこのファイル内で完結している（外部utilに依存しない）。
    同等の共通処理は resources/js/utils/csv.js にもあり、他ページはそちらを利用できる。
-->
<template>
  <div class="max-w-4xl space-y-4">
    <h1 class="page-title mb-2">楽々明細CSV出力</h1>
    <p class="text-sm text-gray-500 -mt-2 mb-4">
      固定情報と明細行を入力し、「楽々明細」取り込み用のCSV（Shift-JIS）をダウンロードします。
    </p>

    <!-- エラー表示 -->
    <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
      ⚠ {{ error }}
    </div>

    <!-- 固定情報カード -->
    <div class="card space-y-4">
      <h2 class="text-sm font-semibold text-gray-700">固定情報（全行共通）</h2>
      <div class="grid grid-cols-2 gap-4">
        <div class="form-row col-span-2">
          <label class="form-label">会社名 <span class="text-red-500">*</span></label>
          <input v-model="fixed.company" type="text" class="input" placeholder="株式会社サンプル" />
        </div>
        <div class="form-row col-span-2">
          <label class="form-label">住所 <span class="text-red-500">*</span></label>
          <input v-model="fixed.address" type="text" class="input" placeholder="東京都千代田区〇〇1-2-3" />
        </div>
        <div class="form-row">
          <label class="form-label">電話番号 <span class="text-red-500">*</span></label>
          <input v-model="fixed.tel" type="text" class="input" placeholder="03-1234-5678" />
        </div>
      </div>
    </div>

    <!-- 明細行カード -->
    <div class="card">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-sm font-semibold text-gray-700">明細行</h2>
        <button type="button" @click="addRow" class="btn-secondary text-xs py-1">+ 行追加</button>
      </div>

      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200 text-xs text-gray-500">
            <th class="text-left pb-2 pr-2 w-1/4">取引日</th>
            <th class="text-left pb-2 pr-2">品目</th>
            <th class="text-right pb-2 pr-2 w-1/4">金額</th>
            <th class="w-6" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, i) in rows" :key="i" class="border-b border-gray-100">
            <td class="py-1.5 pr-2">
              <input v-model="row.date" type="date" class="input" />
            </td>
            <td class="py-1.5 pr-2">
              <input v-model="row.item" type="text" class="input" placeholder="コンサルティング費用" />
            </td>
            <td class="py-1.5 pr-2">
              <input v-model.number="row.amount" type="number" class="input text-right" min="0" />
            </td>
            <td class="py-1.5 text-center">
              <button type="button" @click="rows.splice(i, 1)" v-if="rows.length > 1" class="text-red-400 text-xs">✕</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- 合計（この金額がCSVの「合計金額」列として全行に出力される） -->
      <div class="mt-4 border-t pt-4 flex justify-end text-sm">
        <div class="flex justify-between w-52 text-base font-bold">
          <span>合計</span><span class="text-blue-700">{{ fmt(total) }}</span>
        </div>
      </div>
    </div>

    <!-- アクション -->
    <div class="flex gap-3">
      <button @click="download" class="btn-primary">📥 CSVダウンロード</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import Encoding from 'encoding-japanese'
import { useFlash } from '../../store/flash.js'

// ── CSV生成・ダウンロード（このページ内で完結。共通処理は utils/csv.js にも同等のものがある） ──

/** CSV出力列のヘッダー（この順序で1行を組み立てる） */
const CSV_HEADER = ['会社名', '住所', '電話番号', '取引日', '品目', '金額', '合計金額']

/**
 * CSV の1フィールドをエスケープする。
 * ダブルクォート・カンマ・改行のいずれかを含む場合は "" で囲む（RFC 4180準拠）。
 */
function escapeCsvField(value) {
  const s = String(value ?? '')
  if (/["\n\r,]/.test(s)) {
    return '"' + s.replace(/"/g, '""') + '"'
  }
  return s
}

/**
 * 固定情報 + 明細行を CSV 文字列に変換する。
 * 合計金額（全明細行の amount 合計）は固定情報と同様、各行に繰り返し含める。
 */
function buildStatementCsv(fixedInfo, items) {
  const totalAmount = items.reduce((s, r) => s + (Number(r.amount) || 0), 0)
  const lines = [CSV_HEADER.map(escapeCsvField).join(',')]

  for (const row of items) {
    lines.push(
      [fixedInfo.company, fixedInfo.address, fixedInfo.tel, row.date, row.item, row.amount, totalAmount]
        .map(escapeCsvField)
        .join(',')
    )
  }

  // Excel等での改行解釈を確実にするためCRLFで連結する
  return lines.join('\r\n')
}

/**
 * CSV文字列を Shift-JIS にエンコードしてファイルとしてダウンロードさせる。
 * 日本語版Excelで文字化けせずに直接開けるよう Shift-JIS を採用している。
 */
function downloadStatementCsv(filename, csvString) {
  // 文字列 → Unicodeコードポイント配列 → Shift-JISバイト配列 の順に変換する
  const unicodeArray = Encoding.stringToCode(csvString)
  const sjisArray = Encoding.convert(unicodeArray, { to: 'SJIS', from: 'UNICODE' })
  const blob = new Blob([new Uint8Array(sjisArray)], { type: 'text/csv' })

  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const flash = useFlash()
const error = ref('')

/** 固定情報（全行共通） */
const fixed = ref({ company: '', address: '', tel: '' })

/** 明細行（取引日・品目・金額） */
const rows = ref([{ date: new Date().toISOString().slice(0, 10), item: '', amount: 0 }])

/** 明細行を1行追加する */
function addRow() {
  rows.value.push({ date: new Date().toISOString().slice(0, 10), item: '', amount: 0 })
}

/** 明細行の金額合計 */
const total = computed(() => rows.value.reduce((s, r) => s + (Number(r.amount) || 0), 0))

function fmt(v) {
  return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(v ?? 0)
}

/** 入力内容を検証する。問題があればエラーメッセージを返す（問題なければ null） */
function validate() {
  if (!fixed.value.company || !fixed.value.address || !fixed.value.tel) {
    return '固定情報（会社名・住所・電話番号）をすべて入力してください。'
  }
  const invalid = rows.value.some((r) => !r.date || !r.item || r.amount === '' || r.amount === null || Number(r.amount) < 0)
  if (invalid) {
    return '明細行の取引日・品目・金額（0以上）をすべて入力してください。'
  }
  return null
}

/** フォーム入力をCSVに変換し、ダウンロードさせる */
function download() {
  const message = validate()
  if (message) {
    error.value = message
    return
  }
  error.value = ''

  const csv = buildStatementCsv(fixed.value, rows.value)
  const filename = `meisai_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}.csv`
  downloadStatementCsv(filename, csv)
  flash.success('CSVをダウンロードしました。')
}
</script>
