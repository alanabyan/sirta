import { reactive, ref, watch, onMounted, onUnmounted } from 'vue'
import api, { errorMessage } from '@/api'
import { useUi } from '@/stores/ui'

/**
 * Daftar data berhalaman dengan pencarian (debounce) & filter.
 *   const list = useList('/warga', { filters: { status: '' } })
 */
export function useList(endpoint, { filters = {}, perPage = 10 } = {}) {
  const ui = useUi()
  const items = ref([])
  const loading = ref(true)
  const error = ref('')
  const q = ref('')
  const filter = reactive({ ...filters })
  const meta = reactive({ page: 1, lastPage: 1, total: 0, from: 0, to: 0 })

  let timer
  let seq = 0

  async function load() {
    const my = ++seq
    loading.value = true
    error.value = ''
    try {
      const params = { page: meta.page, per_page: perPage, q: q.value || undefined }
      for (const [k, v] of Object.entries(filter)) if (v !== '' && v != null) params[k] = v
      const { data } = await api.get(endpoint, { params })
      if (my !== seq) return // respons usang
      items.value = data.data
      meta.lastPage = data.last_page
      meta.total = data.total
      meta.from = data.from ?? 0
      meta.to = data.to ?? 0
      if (meta.page > data.last_page && data.last_page > 0) {
        meta.page = data.last_page
        return load()
      }
    } catch (e) {
      if (my === seq) error.value = errorMessage(e)
    } finally {
      if (my === seq) loading.value = false
    }
  }

  watch(q, () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
      meta.page = 1
      load()
    }, 300)
  })
  watch(filter, () => {
    meta.page = 1
    load()
  })
  onMounted(load)
  onUnmounted(() => clearTimeout(timer))

  function goto(p) {
    meta.page = p
    load()
  }

  /** Hapus dengan konfirmasi. */
  async function remove(item, { title, message }) {
    if (!(await ui.ask({ title, message, confirmText: 'Ya, hapus', danger: true }))) return false
    try {
      const { data } = await api.delete(`${endpoint}/${item.id}`)
      ui.success(data.message || 'Data dihapus.')
      await load()
      return true
    } catch (e) {
      ui.error(errorMessage(e))
      return false
    }
  }

  return { items, loading, error, q, filter, meta, load, goto, remove }
}
