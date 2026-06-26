<!--
  StatCard コンポーネント

  ダッシュボードの KPI カード。
  ラベル・値・アイコン・カラーを props で受け取り、統一されたカードデザインで表示する。

  props:
    label  - カード上部の説明テキスト（例: '当月売上'）
    value  - 表示する値（例: '¥1,234,567'）
    icon   - 右側の絵文字アイコン（例: '💹'）
    color  - 値テキストの色テーマ（'blue' | 'green' | 'red' | 'yellow'）
-->
<template>
  <div class="bg-white rounded-lg border shadow-sm p-5 flex justify-between items-center">
    <div>
      <!-- カードラベル -->
      <p class="text-sm text-gray-500">{{ label }}</p>
      <!-- 値（カラークラス適用） -->
      <p class="text-2xl font-bold mt-1" :class="colorCls">{{ value }}</p>
    </div>
    <!-- 右側アイコン -->
    <span class="text-3xl">{{ icon }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: String,
  value: String,
  icon:  String,
  color: { type: String, default: 'blue' }, // デフォルトはブルー
})

/** color prop を Tailwind クラスに変換する */
const colorCls = computed(() => ({
  blue:   'text-blue-700',
  green:  'text-green-700',
  red:    'text-red-600',
  yellow: 'text-yellow-700',
}[props.color] ?? 'text-gray-800'))
</script>
