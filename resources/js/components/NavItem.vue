<!--
  NavItem コンポーネント

  サイドバーのナビゲーションリンク。
  現在のルートと href が一致する場合に「アクティブ」スタイルを適用する。
  トップページ（/ と /dashboard）は同一ページとして扱う。
-->
<template>
  <a :href="href"
     class="flex items-center px-4 py-2 mx-2 rounded-md text-sm transition-colors"
     :class="isActive
       ? 'bg-blue-600 text-white'
       : 'text-gray-300 hover:bg-gray-700 hover:text-white'">
    <!-- アイコン絵文字 -->
    <span class="mr-3">{{ icon }}</span>
    <!-- メニューラベル（スロット） -->
    <slot />
  </a>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '../router/index.js'

const props = defineProps({
  href: String,  // リンク先（例: '#/clients'）
  icon: String,  // 絵文字アイコン
})

/**
 * アクティブ判定
 * href の # を除いたパスが現在のハッシュと一致するか確認する。
 * ダッシュボード系は / と /dashboard を同一視する。
 */
const isActive = computed(() => {
  const target  = props.href.replace(/^#/, '')
  const current = router.currentHash.value
  if (target === '/' || target === '/dashboard') {
    return current === '/' || current === '/dashboard'
  }
  return current.startsWith(target)
})
</script>
