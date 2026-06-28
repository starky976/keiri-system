<!-- 予算登録フォーム -->
<template>
  <div class="max-w-lg">
    <h1 class="page-title mb-6">予算登録</h1>
    <form @submit.prevent="save" class="card space-y-4">
      <div class="form-row">
        <label class="form-label">年度 <span class="text-red-500">*</span></label>
        <input v-model="form.fiscal_year" type="number" class="input" required />
      </div>
      <div class="form-row">
        <label class="form-label">月（空欄 = 年次予算）</label>
        <select v-model="form.month" class="input">
          <option value="">年次</option>
          <option v-for="m in 12" :key="m" :value="m">{{ m }}月</option>
        </select>
      </div>
      <div class="form-row">
        <label class="form-label">勘定科目 <span class="text-red-500">*</span></label>
        <select v-model="form.account_item_id" class="input" required>
          <option value="">選択してください</option>
          <option v-for="a in accountItems" :key="a.id" :value="a.id">{{ a.name }}</option>
        </select>
      </div>
      <div class="form-row">
        <label class="form-label">部門</label>
        <select v-model="form.department_id" class="input">
          <option value="">全社（部門指定なし）</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
      </div>
      <div class="form-row">
        <label class="form-label">予算額 <span class="text-red-500">*</span></label>
        <input v-model="form.amount" type="number" step="1" min="0" class="input" required />
      </div>
      <div class="form-row">
        <label class="form-label">摘要</label>
        <input v-model="form.note" type="text" class="input" />
      </div>
      <div class="flex gap-2 justify-end">
        <button type="button" @click="router.back()" class="btn-secondary">キャンセル</button>
        <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? '保存中...' : '登録' }}</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/index.js'
import { router } from '../../router/index.js'
import { useFlash } from '../../store/flash.js'

const flash        = useFlash()
const saving       = ref(false)
const accountItems = ref([])
const departments  = ref([])

const form = ref({
  fiscal_year: new Date().getFullYear(), month: '', account_item_id: '', department_id: '', amount: '', note: ''
})

async function save() {
  saving.value = true
  try {
    await api.post('/budgets', { ...form.value, month: form.value.month || null })
    flash.success('予算を登録しました。')
    router.push('/budgets')
  } finally { saving.value = false }
}

onMounted(async () => {
  const [ai, dep] = await Promise.all([api.get('/account-items'), api.get('/departments')])
  accountItems.value = ai.data
  departments.value  = dep.data
})
</script>
