import axios from 'axios'

const TOKEN_KEY = 'sirta_token'

export const tokenStore = {
  get: () => localStorage.getItem(TOKEN_KEY),
  set: (t) => localStorage.setItem(TOKEN_KEY, t),
  clear: () => localStorage.removeItem(TOKEN_KEY),
}

const api = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = tokenStore.get()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Sesi berakhir → kembali ke halaman masuk. (Dipasang lewat router agar tidak ada import melingkar.)
let onUnauthorized = () => {}
export const setUnauthorizedHandler = (fn) => (onUnauthorized = fn)

api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401 && !err.config.url.includes('/login')) {
      tokenStore.clear()
      onUnauthorized()
    }
    return Promise.reject(err)
  },
)

/** Ambil pesan galat yang ramah dari respons Laravel. */
export function errorMessage(err) {
  if (!err.response) return 'Tidak dapat terhubung ke server. Periksa koneksi atau pastikan backend berjalan.'
  const d = err.response.data
  if (d?.errors) return Object.values(d.errors)[0][0]
  return d?.message || 'Terjadi kesalahan. Silakan coba lagi.'
}

/** Galat validasi per kolom: { nama: 'Wajib diisi' } */
export function fieldErrors(err) {
  const e = err.response?.data?.errors
  if (!e) return {}
  return Object.fromEntries(Object.entries(e).map(([k, v]) => [k, v[0]]))
}

export default api
