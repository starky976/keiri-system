<!--
  取引先 登録・編集フォーム

  URL パラメータ :id の有無で登録（POST）と更新（PUT）を切り替える。
  バリデーションエラーはフィールド下に表示する。

  ルート:
    /clients/create    → 新規登録
    /clients/:id/edit  → 編集
-->
<template>
  <div class="max-w-2xl">
    <form @submit.prevent="submit" class="bg-white rounded-lg border shadow-sm p-6 space-y-4">

      <!-- コード・区分（2列） -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">取引先コード <span class="text-red-500">*</span></label>
          <input v-model="form.code" type="text" class="input" placeholder="C001" />
          <p v-if="errors.code" class="err">{{ errors.code[0] }}</p>
        </div>
        <div>
          <label class="label">区分 <span class="text-red-500">*</span></label>
          <select v-model="form.type" class="input">
            <option value="customer">得意先</option>
            <option value="vendor">仕入先</option>
            <option value="both">両方</option>
          </select>
        </div>
      </div>

      <!-- 取引先名 -->
      <div>
        <label class="label">取引先名 <span class="text-red-500">*</span></label>
        <input v-model="form.name" type="text" class="input" placeholder="株式会社サンプル" />
        <p v-if="errors.name" class="err">{{ errors.name[0] }}</p>
      </div>

      <!-- 取引先名（カナ） -->
      <div>
        <label class="label">取引先名（カナ）</label>
        <input v-model="form.name_kana" type="text" class="input" />
      </div>

      <!-- 郵便番号・電話番号（2列） -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">郵便番号</label>
          <input v-model="form.postal_code" type="text" class="input" placeholder="123-4567" />
        </div>
        <div>
          <label class="label">電話番号</label>
          <input v-model="form.phone" type="text" class="input" />
        </div>
      </div>

      <!-- 住所 -->
      <div>
        <label class="label">住所</label>
        <input v-model="form.address" type="text" class="input" />
      </div>

      <!-- メールアドレス・担当者名（2列） -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">メールアドレス</label>
          <input v-model="form.email" type="email" class="input" />
          <p v-if="errors.email" class="err">{{ errors.email[0] }}</p>
        </div>
        <div>
          <label class="label">担当者名</label>
          <input v-model="form.contact_person" type="text" class="input" />
        </div>
      </div>

      <!-- 支払サイト・有効フラグ（2列） -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">支払サイト（日）</label>
          <input v-model.number="form.payment_terms" type="number" class="input" min="0" max="180" />
        </div>
        <div class="flex items-center pt-5">
          <input v-model="form.is_active" type="checkbox" id="active" class="mr-2 rounded" />
          <label for="active" class="text-sm text-gray-700">有効</label>
        </div>
      </div>

      <!-- 備考 -->
      <div>
        <label class="label">備考</label>
        <textarea v-model="form.notes" class="input" rows="2" />
      </div>

      <!-- フォームアクション -->
      <div class="flex gap-3 pt-2">
        <!-- 登録/更新ボタン（送信中は disabled） -->
        <button type="submit" :disabled="saving" class="btn-primary">
          {{ id ? '更新する' : '登録する' }}
        </button>
        <a href="#/clients" class="btn-secondary">キャンセル</a>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/index.js'
import { router, useRoute } from '../../router/index.js'
import { useFlash } from '../../store/flash.js'

const route = useRoute()
const flash = useFlash()

/** URL パラメータの :id（新規登録時は undefined） */
const id     = route.params.value.id
/** 送信中フラグ（二重送信防止） */
const saving = ref(false)
/** バリデーションエラー（フィールド名 → エラーメッセージ配列） */
const errors = ref({})

/** フォームの初期値 */
const form = ref({
  code: '', name: '', name_kana: '', type: 'customer',
  postal_code: '', address: '', phone: '', email: '',
  contact_person: '', payment_terms: 30, is_active: true, notes: '',
})

/** 編集時は既存データをフォームにセットする */
onMounted(async () => {
  if (id) {
    const res = await api.get(`/clients/${id}`)
    Object.assign(form.value, res.data)
  }
})

/**
 * フォーム送信処理
 * id がある場合は PUT（更新）、ない場合は POST（登録）を実行する。
 * 成功時は一覧ページへ遷移、失敗時はエラーを表示する。
 */
async function submit() {
  saving.value = true
  errors.value = {}
  try {
    if (id) {
      await api.put(`/clients/${id}`, form.value)
      flash.success('取引先を更新しました。')
    } else {
      await api.post('/clients', form.value)
      flash.success('取引先を登録しました。')
    }
    router.push('#/clients') // 一覧へ戻る
  } catch (e) {
    // Laravel バリデーションエラー (422) の errors オブジェクトを取得
    errors.value = e.response?.data?.errors ?? {}
  } finally {
    saving.value = false
  }
}
</script>
