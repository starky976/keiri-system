<!--
  LoadingSpinner コンポーネント

  テーブルやページ全体のローディング中に表示するスピナー。

  props:
    colspan - テーブル内で使う場合の <td colspan="N">（デフォルト: 1）
    message - 表示メッセージ（デフォルト: 読み込み中...）
-->
<template>
  <tr>
    <td :colspan="colspan" class="py-10 text-center">
      <div class="inline-flex flex-col items-center gap-2 text-gray-400">
        <!-- SVG スピナー -->
        <svg class="animate-spin h-6 w-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
        <span class="text-sm">{{ message }}</span>
      </div>
    </td>
  </tr>
</template>

<script setup>
defineProps({
  colspan: { type: Number, default: 1 },
  message: { type: String, default: '読み込み中...' },
})
</script>
