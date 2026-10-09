import { defineStore } from 'pinia'
import api, { tokenStore } from '@/api'

export const useAuth = defineStore('auth', {
  state: () => ({ user: null, ready: false }),
  getters: {
    isLoggedIn: (s) => !!s.user,
    isAdmin: (s) => s.user?.role === 'administrator',
    /** Tambah/ubah data: semua peran kecuali Bendahara (hanya lihat). */
    canWrite: (s) => !!s.user && s.user.role !== 'bendahara',
    /** Identitas RT, tanda tangan & stempel. */
    canSetting: (s) => ['administrator', 'ketua_rt'].includes(s.user?.role),
    /** Verifikasi, hapus data, kelola pengurus & template. */
    canDecide: (s) => ['administrator', 'ketua_rt', 'sekretaris'].includes(s.user?.role),
  },
  actions: {
    async login(username, password) {
      const { data } = await api.post('/login', { username, password })
      tokenStore.set(data.token)
      this.user = data.user
    },
    async restore() {
      if (tokenStore.get()) {
        try {
          this.user = (await api.get('/me')).data.user
        } catch {
          tokenStore.clear()
        }
      }
      this.ready = true
    },
    async logout() {
      try {
        await api.post('/logout')
      } catch {
        /* token sudah tidak valid — abaikan */
      }
      this.clear()
    },
    clear() {
      tokenStore.clear()
      this.user = null
    },
  },
})
