import { ref } from 'vue'
import api, { errorMessage, fieldErrors } from '@/api'
import { useUi } from '@/stores/ui'

/** Simpan (POST) atau perbarui (PUT) satu data, lengkap dengan galat per kolom. */
export function useSave(endpoint) {
  const ui = useUi()
  const saving = ref(false)
  const errors = ref({})

  async function save(id, payload, config) {
    saving.value = true
    errors.value = {}
    try {
      const { data } = id
        ? await api.put(`${endpoint}/${id}`, payload, config)
        : await api.post(endpoint, payload, config)
      ui.success(data.message || 'Data berhasil disimpan.')
      return data.data ?? true
    } catch (e) {
      errors.value = fieldErrors(e)
      // Galat validasi sudah tampil di bawah kolom; selain itu tampilkan sebagai toast.
      if (!Object.keys(errors.value).length) ui.error(errorMessage(e))
      else ui.error('Periksa kembali isian yang ditandai merah.')
      return null
    } finally {
      saving.value = false
    }
  }

  return { saving, errors, save }
}
