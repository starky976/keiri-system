/**
 * Axios グローバル設定
 *
 * window.axios に Axios インスタンスをセットし、
 * サーバー側で Ajax リクエストと判別できるよう
 * X-Requested-With ヘッダーをデフォルトで付与する。
 */
import axios from 'axios';
window.axios = axios;

// Laravel の Request::ajax() が true を返すために必要なヘッダー
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
