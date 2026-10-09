import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@/stores/auth'
import { setUnauthorizedHandler } from '@/api'

const AppLayout = () => import('@/layouts/AppLayout.vue')

const routes = [
  { path: '/masuk', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { guest: true, title: 'Masuk' } },
  { path: '/ajukan', name: 'ajukan', component: () => import('@/views/AjukanView.vue'), meta: { title: 'Ajukan Surat' } },
  { path: '/lacak/:kode?', name: 'lacak', component: () => import('@/views/PublicTrackingView.vue'), meta: { title: 'Lacak Pengajuan' } },
  {
    path: '/',
    component: AppLayout,
    meta: { auth: true },
    children: [
      { path: '', name: 'dashboard', component: () => import('@/views/DashboardView.vue'), meta: { title: 'Beranda', subtitle: 'Ringkasan kegiatan RT hari ini' } },

      { path: 'warga', name: 'warga', component: () => import('@/views/WargaView.vue'), meta: { title: 'Data Warga', group: 'Data Induk', subtitle: 'Daftar seluruh penduduk RT' } },
      { path: 'keluarga', name: 'keluarga', component: () => import('@/views/KeluargaView.vue'), meta: { title: 'Data Keluarga', group: 'Data Induk', subtitle: 'Kartu Keluarga dan anggotanya' } },
      { path: 'pengurus', name: 'pengurus', component: () => import('@/views/PengurusView.vue'), meta: { title: 'Pengurus RT', group: 'Data Induk', subtitle: 'Susunan kepengurusan RT' } },

      { path: 'layanan', name: 'layanan', component: () => import('@/views/PengajuanView.vue'), meta: { title: 'Permohonan Warga', group: 'Pelayanan Warga', subtitle: 'Semua permohonan surat dari warga' } },
      { path: 'verifikasi', name: 'verifikasi', component: () => import('@/views/VerifikasiView.vue'), meta: { title: 'Verifikasi', group: 'Pelayanan Warga', subtitle: 'Periksa dan proses permohonan yang masuk' } },
      { path: 'tracking', name: 'tracking', component: () => import('@/views/TrackingView.vue'), meta: { title: 'Lacak Permohonan', group: 'Pelayanan Warga', subtitle: 'Lihat perjalanan sebuah permohonan' } },

      { path: 'surat-masuk', name: 'surat-masuk', component: () => import('@/views/SuratMasukView.vue'), meta: { title: 'Surat Masuk', group: 'Surat & Arsip', subtitle: 'Surat yang diterima RT' } },
      { path: 'surat-keluar', name: 'surat-keluar', component: () => import('@/views/SuratKeluarView.vue'), meta: { title: 'Surat Keluar', group: 'Surat & Arsip', subtitle: 'Surat yang diterbitkan RT' } },
      { path: 'template', name: 'template', component: () => import('@/views/TemplateView.vue'), meta: { title: 'Template Surat', group: 'Surat & Arsip', subtitle: 'Kerangka surat siap pakai' } },
      { path: 'arsip', name: 'arsip', component: () => import('@/views/ArsipView.vue'), meta: { title: 'Arsip Digital', group: 'Surat & Arsip', subtitle: 'Penyimpanan dokumen RT' } },

      { path: 'pengguna', name: 'pengguna', component: () => import('@/views/PenggunaView.vue'), meta: { title: 'Pengguna Sistem', group: 'Pengaturan', subtitle: 'Akun dan hak akses', admin: true } },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (to) => {
  const auth = useAuth()
  if (!auth.ready) await auth.restore()
  if (to.meta.auth && !auth.isLoggedIn) return { name: 'login', query: { next: to.fullPath } }
  if (to.meta.guest && auth.isLoggedIn) return { name: 'dashboard' }
  if (to.matched.some((r) => r.meta.admin) && !auth.isAdmin) return { name: 'dashboard' }
})

router.afterEach((to) => {
  document.title = `${to.meta.title ?? 'SIRTA'} · SIRTA`
})

setUnauthorizedHandler(() => {
  useAuth().clear()
  router.push({ name: 'login', query: { next: router.currentRoute.value.fullPath } })
})

export default router
