/**
 * 日付フォーマットユーティリティ
 *
 * LaravelのAPIは日付を "2026-02-28T00:00:00.000000Z" のような
 * ISO 8601 形式で返す。このユーティリティで YYYY/MM/DD に統一する。
 */

/**
 * 日付文字列を YYYY/MM/DD 形式に変換する
 * @param {string|null} value - ISO日付文字列 ("2026-02-28T00:00:00.000000Z" など)
 * @returns {string} "2026/02/28" 形式。値がなければ "─"
 */
export function fmtDate(value) {
  if (!value) return '─'
  // ISO文字列・YYYY-MM-DD どちらも Date で解釈できる
  // UTCとして扱い、タイムゾーンのズレを防ぐ
  const d = new Date(value)
  if (isNaN(d.getTime())) return '─'
  const y = d.getUTCFullYear()
  const m = String(d.getUTCMonth() + 1).padStart(2, '0')
  const day = String(d.getUTCDate()).padStart(2, '0')
  return `${y}/${m}/${day}`
}
