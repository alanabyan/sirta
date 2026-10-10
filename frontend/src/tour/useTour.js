import { driver } from 'driver.js'
import 'driver.js/dist/driver.css'
import { useRoute, useRouter } from 'vue-router'
import { nextTick } from 'vue'
import { useAuth } from '@/stores/auth'
import { useUi } from '@/stores/ui'
import { susunLangkah } from './langkah'

const MOBILE = () => window.matchMedia('(max-width: 960px)').matches

/** Tunggu sampai elemen muncul (halaman dimuat belakangan dari API). */
function tunggu(selector, ms = 8000) {
  return new Promise((resolve) => {
    const mulai = Date.now()
    const cek = () => {
      if (document.querySelector(selector)) return resolve(true)
      if (Date.now() - mulai > ms) return resolve(false)
      setTimeout(cek, 120)
    }
    cek()
  })
}

let berjalan = false

export function useTour() {
  const auth = useAuth()
  const ui = useUi()
  const route = useRoute()
  const router = useRouter()

  async function jalankan() {
    if (berjalan || !auth.user) return
    berjalan = true

    // Menu samping yang terlipat tetap harus bisa disorot: buka semua kelompok selama panduan.
    ui.tourAktif = true
    await nextTick()

    const mobile = MOBILE()
    const langkah = susunLangkah({ auth, mobile })

    const d = driver({
      steps: langkah,
      popoverClass: 'sirta-tour',
      showProgress: true,
      progressText: '{{current}} dari {{total}}',
      nextBtnText: 'Lanjut',
      prevBtnText: 'Kembali',
      doneBtnText: 'Selesai',
      closeBtnLabel: 'Tutup panduan',
      overlayColor: '#04120f',
      overlayOpacity: 0.62,
      stagePadding: 8,
      stageRadius: 12,
      smoothScroll: true,
      allowKeyboardControl: true,
      overlayClickBehavior: 'none', // klik di area gelap tidak menutup panduan secara tak sengaja
      disableActiveInteraction: true, // elemen yang disorot tidak bisa diklik saat panduan berjalan
      onPopoverRender: (popover, { driver: dr }) => {
        // Tombol teks "Lewati" yang jelas, selain ikon ×, kecuali di langkah terakhir.
        if (dr.isLastStep()) return
        const lewati = document.createElement('button')
        lewati.type = 'button'
        lewati.className = 'tour-lewati'
        lewati.textContent = 'Lewati'
        lewati.addEventListener('click', () => dr.destroy())
        popover.footer.prepend(lewati)
      },
      onDestroyed: async () => {
        berjalan = false
        ui.tourAktif = false
        ui.sidebarOpen = false
        // Selesai maupun dilewati sama-sama dianggap "sudah melihat"; bisa diulang lewat menu pengguna.
        try {
          await auth.tandaiTour()
        } catch {
          /* gangguan jaringan — panduan akan tampil lagi saat login berikutnya */
        }
      },
    })
    d.drive()
  }

  /** Otomatis saat pertama kali masuk (belum pernah selesai/dilewati). */
  async function mulaiOtomatis() {
    if (!auth.user || auth.user.tour_selesai_at) return
    if (route.path === '/') await tunggu('[data-tour="dash-angka"]')
    else await tunggu('[data-tour="sidebar"]')
    await new Promise((r) => setTimeout(r, 500)) // beri waktu animasi halaman selesai
    jalankan()
  }

  /** Dari menu pengguna: kembali ke Beranda lebih dulu agar langkah Beranda ikut tampil. */
  async function ulangi() {
    if (route.path !== '/') {
      await router.push('/')
      await tunggu('[data-tour="dash-angka"]')
    }
    ui.sidebarOpen = false
    await new Promise((r) => setTimeout(r, 300))
    jalankan()
  }

  return { mulaiOtomatis, ulangi }
}
