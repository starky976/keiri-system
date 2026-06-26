/**
 * アプリケーションエントリーポイント
 *
 * Vue アプリを #app 要素にマウントする。
 * Tailwind CSS も同じバンドルに含めている。
 */
import { createApp } from 'vue'
import App from './App.vue'
import '../css/app.css'

// Vue インスタンスを生成して DOM にマウント
createApp(App).mount('#app')
