<script setup>
import { reactive, ref } from 'vue'
import api, { errorMessage, fieldErrors } from '@/api'
import { useUi } from '@/stores/ui'
import BaseModal from './BaseModal.vue'
import FormField from './FormField.vue'

const emit = defineEmits(['close'])
const ui = useUi()
const form = reactive({ current_password: '', password: '', password_confirmation: '' })
const errors = ref({})
const saving = ref(false)

async function submit() {
  saving.value = true
  errors.value = {}
  try {
    const { data } = await api.put('/password', form)
    ui.success(data.message)
    emit('close')
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) ui.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseModal title="Ubah kata sandi" subtitle="Gunakan minimal 8 karakter." size="sm" :saving="saving" @close="emit('close')" @save="submit">
    <div class="stack">
      <FormField label="Kata sandi saat ini" :error="errors.current_password" required>
        <input v-model="form.current_password" class="input" type="password" autocomplete="current-password" required>
      </FormField>
      <FormField label="Kata sandi baru" :error="errors.password" required>
        <input v-model="form.password" class="input" type="password" autocomplete="new-password" minlength="8" required>
      </FormField>
      <FormField label="Ulangi kata sandi baru" required>
        <input v-model="form.password_confirmation" class="input" type="password" autocomplete="new-password" required>
      </FormField>
    </div>
  </BaseModal>
</template>
