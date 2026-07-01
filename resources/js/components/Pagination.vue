<!--
  Pagination コンポーネント

  Laravel のページネーションレスポンス（data.links）を受け取り、
  ページボタンを描画する。
  ページが1ページしかない場合は表示しない（v-if="data?.last_page > 1"）。

  emit:
    page(pageNumber: string) - ページ番号クリック時に親へ通知する
-->
<template>
  <div v-if="data?.last_page > 1" class="flex items-center justify-between mt-4">
    <!-- 件数表示: 「1〜20件 / 全35件」 -->
    <p class="text-sm text-gray-500">{{ data.from }}〜{{ data.to }}件 / 全{{ data.total }}件</p>

    <!-- ページボタン群 -->
    <div class="flex gap-1">
      <button
        v-for="link in data.links"
        :key="link.label"
        v-html="link.label"
        :disabled="!link.url"
        @click="link.url && go(link.url)"
        class="px-3 py-1 text-sm rounded border transition"
        :class="link.active
          ? 'bg-blue-600 text-white border-blue-600'
          : link.url
            ? 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 cursor-pointer'
            : 'bg-white text-gray-300 border-gray-200 cursor-default'"
      />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  data: Object, // Laravel paginate() のレスポンス全体
})
const emit = defineEmits(['page'])

/**
 * ページボタンクリック時の処理
 * Laravel が返す URL（例: /api/clients?page=2）から page 番号を取り出して
 * 親コンポーネントへ emit する。
 *
 * @param {string} url - Laravel のページネーションリンク URL
 */
function go(url) {
  const page = new URL(url).searchParams.get('page')
  emit('page', page)
}
</script>
