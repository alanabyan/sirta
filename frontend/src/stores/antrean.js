import { defineStore } from 'pinia'
import api from '@/api'
import { useUi } from '@/stores/ui'

const INTERVAL = 60_000

/** Hitungan antrean untuk penanda menu; diperbarui tiap menit dan saat tab kembali aktif. */
export const useAntrean = defineStore('antrean', {
  state: () => ({ perluVerifikasi: 0, suratBaru: 0, loaded: false, timer: null }),
  actions: {
    async refresh() {
      try {
        const { data } = await api.get('/antrean')
        const baru = data.data.perlu_verifikasi
        if (this.loaded && baru > this.perluVerifikasi) {
          const n = baru - this.perluVerifikasi
          useUi().success(`${n} permohonan baru menunggu verifikasi.`)
        }
        this.perluVerifikasi = baru
        this.suratBaru = data.data.surat_baru
        this.loaded = true
      } catch {
        /* gangguan jaringan sesaat — coba lagi pada putaran berikutnya */
      }
    },
    start() {
      if (this.timer) return
      this.refresh()
      this.timer = setInterval(() => !document.hidden && this.refresh(), INTERVAL)
      document.addEventListener('visibilitychange', this.onVisible)
    },
    stop() {
      clearInterval(this.timer)
      this.timer = null
      this.loaded = false
      this.perluVerifikasi = this.suratBaru = 0
      document.removeEventListener('visibilitychange', this.onVisible)
    },
    onVisible() {
      if (!document.hidden) useAntrean().refresh()
    },
  },
})
