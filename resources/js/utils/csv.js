/**
 * CSV ユーティリティ（機能16: 楽々明細 CSV出力）
 *
 * 入力フォーム（固定情報 + 明細行）を「楽々明細」向けの CSV に変換し、
 * ブラウザからダウンロードさせるための2つの関数を提供する。
 *
 *  - buildStatementCsv() : フォーム入力 → CSV文字列 に変換する
 *  - downloadCsv()       : CSV文字列 を Shift-JIS でエンコードしてダウンロードさせる
 *
 * CSVは行ごとに固定情報（会社名・住所・電話番号）を繰り返し含める
 * "フラット形式" を採用している。セクション分けした形式より構造が単純で、
 * 表計算ソフトや他システムでの読み込みが安定するため。
 */
import Encoding from 'encoding-japanese'

/** CSV出力列のヘッダー（この順序で1行を組み立てる） */
const HEADER = ['会社名', '住所', '電話番号', '取引日', '品目', '金額', '合計金額']

/**
 * CSV の1フィールドをエスケープする。
 * ダブルクォート・カンマ・改行のいずれかを含む場合は "" で囲む（RFC 4180準拠）。
 */
function escapeField(value) {
  const s = String(value ?? '')
  if (/["\n\r,]/.test(s)) {
    return '"' + s.replace(/"/g, '""') + '"'
  }
  return s
}

/**
 * 固定情報 + 明細行を CSV 文字列に変換する。
 * 合計金額（全明細行の amount 合計）は固定情報と同様、各行に繰り返し含める。
 *
 * @param {{company: string, address: string, tel: string}} fixed - 全行共通の固定情報
 * @param {Array<{date: string, item: string, amount: number|string}>} rows - 明細行（1行 = 1取引）
 * @returns {string} ヘッダー行付きのCSV文字列（改行はCRLF）
 */
export function buildStatementCsv(fixed, rows) {
  // 全行共通で持たせる合計金額（現状は明細行 amount の単純合計）
  const total = rows.reduce((s, r) => s + (Number(r.amount) || 0), 0)

  const lines = [HEADER.map(escapeField).join(',')]

  for (const row of rows) {
    lines.push(
      [fixed.company, fixed.address, fixed.tel, row.date, row.item, row.amount, total]
        .map(escapeField)
        .join(',')
    )
  }

  // Excel等での改行解釈を確実にするためCRLFで連結する
  return lines.join('\r\n')
}

/**
 * CSV文字列を Shift-JIS にエンコードしてファイルとしてダウンロードさせる。
 * 日本語版Excelで文字化けせずに直接開けるよう Shift-JIS を採用している。
 *
 * @param {string} filename   - ダウンロードファイル名（例: 'meisai_20260720.csv'）
 * @param {string} csvString  - buildStatementCsv() で生成したCSV文字列
 */
export function downloadCsv(filename, csvString) {
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
