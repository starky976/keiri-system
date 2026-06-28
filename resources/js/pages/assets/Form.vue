<!-- 固定資産 登録・編集フォーム -->
<template>
  <div class="max-w-lg">
    <h1 class="page-title mb-6">{{ isEdit ? '固定資産 編集' : '固定資産 登録' }}</h1>
    <form @submit.prevent="save" class="card space-y-4">
      <div class="form-row">
        <label class="form-label">資産名称 <span class="text-red-500">*</span></label>
        <input v-model="form.name" type="text" class="input" required />
      </div>
      <div class="form-row">
        <label class="form-label">種別 <span class="text-red-500">*</span></label>
        <select v-model="form.category" class="input" required>
          <option value="">選択してください</option>
          <option value="building">建物</option>
          <option value="vehicle">車両</option>
          <option value="equipment">器具備品</option>
          <option value="software">ソフトウェア</option>
          <option value="land">土地</option>
        </select>
      </div>
      <div class="form-row">
        <label class="form-label">取得日 <span class="text-red-500">*</span></label>
        <input v-model="form.acquisition_date" type="date" class="input" required />
      </div>
      <div class="form-row">
        <label class="form-label">取得価額 <span class="text-red-500">*</span></label>
        <input v-model="form.acquisition_amount" type="number" step="1" min="1" class="input" required />
      </div>
      <div class="form-row">
        <label class="form-label">耐用年数（年）<span class="text-red-500">*</span></label>
        <input v-model="form.useful_life" type="number" min="1" max="100" class="input" required />
      </div>
      <div class="form-row">
        <label class="form-label">償却方法 <span class="text-red-500">*</span></label>
        <select v-model="form.depreciation_method" class="input" required>
          <option value="straight_line">定額法</option>
          <option value="declining_balance">定率法</option>
        </select>
      </div>
      <div class="form-row">
        <label class="form-label">残存価額（円）</label>
        <input v-model="form.residual_value" type="number" step="1" min="0" class="input" />
      </div>
      <template v-if="isEdit">
        <div class="form-row">
          <label class="form-label">廃棄・売却日</label>
          <input v-model="form.disposal_date" type="date" class="input" />
        </div>
        <div class="form-row">
          <label class="form-label">売却額</label>
          <input v-model="form.disposal_amount" type="number" step="1" min="0" class="input" />
        </div>
      </template>
      <div class="form-row">
        <label class="form-label">摘要</label>
        <textarea v-model="form.note" rows="2" class="input" />
      </div>
      <div class="flex gap-2 justify-end">
        <button type="button" @click="router.back()" class="btn-secondary">キャンセル</button>
        <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? '保存中...' : '保存' }}</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/index.js'
import { router, useRoute } from '../../router/index.js'
import { useFlash } from '../../store/flash.js'

const { params } = useRoute()
const flash      = useFlash()
const saving     = ref(false)
const isEdit     = ref(false)

const form = ref({
  name: '', category: '', acquisition_date: '', acquisition_amount: '',
  useful_life: '', depreciation_method: 'straight_line', residual_value: 1,
  disposal_date: '', disposal_amount: '', note: ''
})

async function save() {
  saving.value = true
  try {
    if (isEdit.value) {
      await api.put(`/fixed-assets/${params.value.id}`, form.value)
    } else {
      await api.post('/fixed-assets', form.value)
    }
    useFlash().success('保存しました。')
    router.push('/assets')
  } finally { saving.value = false }
}

onMounted(async () => {
  if (params.value.id) {
    isEdit.value = true
    const r = await api.get(`/fixed-assets/${params.value.id}`)
    Object.assign(form.value, r.data)
  }
})
</script>
