<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  Home, Users, UsersRound, Award, FilePlus2, ClipboardCheck, Search, Inbox, Send,
  FileText, Archive, Settings, PenLine, Menu, Moon, Sun, LogOut, KeyRound, ChevronDown, X,
} from 'lucide-vue-next'
import { useAuth } from '@/stores/auth'
import { useUi } from '@/stores/ui'
import { useAntrean } from '@/stores/antrean'
import { inisial } from '@/utils'
import ChangePasswordModal from '@/components/ChangePasswordModal.vue'

const auth = useAuth()
const ui = useUi()
const antrean = useAntrean()
const route = useRoute()
const router = useRouter()

const menu = computed(() => [
  { items: [{ to: '/', label: 'Beranda', icon: Home }] },
  {
    title: 'Pelayanan Warga',
    items: [
      { to: '/layanan', label: 'Permohonan Warga', icon: FilePlus2 },
      { to: '/verifikasi', label: 'Verifikasi', icon: ClipboardCheck, badge: 'perluVerifikasi' },
      { to: '/tracking', label: 'Lacak Permohonan', icon: Search },
    ],
  },
  {
    title: 'Surat & Arsip',
    items: [
      { to: '/surat-masuk', label: 'Surat Masuk', icon: Inbox, badge: 'suratBaru' },
      { to: '/surat-keluar', label: 'Surat Keluar', icon: Send },
      { to: '/template', label: 'Template Surat', icon: FileText },
      { to: '/arsip', label: 'Arsip Digital', icon: Archive },
    ],
  },
  {
    title: 'Data Induk',
    items: [
      { to: '/warga', label: 'Data Warga', icon: Users },
      { to: '/keluarga', label: 'Data Keluarga', icon: UsersRound },
      { to: '/pengurus', label: 'Pengurus RT', icon: Award },
    ],
  },
  ...(auth.canSetting
    ? [{
        title: 'Pengaturan',
        items: [
          { to: '/pengaturan', label: 'Identitas & Tanda Tangan', icon: PenLine },
          ...(auth.isAdmin ? [{ to: '/pengguna', label: 'Pengguna Sistem', icon: Settings }] : []),
        ],
      }]
    : []),
])

const hitung = (m) => (m.badge ? antrean[m.badge] : 0)
const hitungGrup = (g) => g.items.reduce((n, m) => n + hitung(m), 0)

onMounted(() => antrean.start())
onUnmounted(() => antrean.stop())

const isActive = (to) => (to === '/' ? route.path === '/' : route.path.startsWith(to))

// Grup menu bisa dibuka/tutup; grup yang berisi halaman aktif otomatis terbuka.
const terbuka = reactive({})
const grupAktif = (g) => g.items.some((m) => isActive(m.to))
const isOpen = (g) => (g.title in terbuka ? terbuka[g.title] : grupAktif(g))
const toggle = (g) => (terbuka[g.title] = !isOpen(g))

const userMenu = ref(false)
const showPassword = ref(false)

watch(() => route.path, () => {
  // Pindah halaman → buka grup tujuan.
  menu.value.forEach((g) => g.title && grupAktif(g) && (terbuka[g.title] = true))
})

watch(() => route.fullPath, () => {
  ui.sidebarOpen = false
  userMenu.value = false
})

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="shell">
    <div v-if="ui.sidebarOpen" class="scrim no-print" @click="ui.sidebarOpen = false" />

    <aside class="sidebar no-print" :class="{ open: ui.sidebarOpen }">
      <div class="brand">
        <div class="logo"><Home :size="20" /></div>
        <div class="grow">
          <b>SIRTA</b>
          <span>Sistem Informasi RT</span>
        </div>
        <button class="btn btn-icon close-side" aria-label="Tutup menu" @click="ui.sidebarOpen = false"><X :size="20" /></button>
      </div>

      <nav aria-label="Menu utama">
        <div v-for="(g, i) in menu" :key="i" class="group">
          <button v-if="g.title" class="group-title" :aria-expanded="isOpen(g)" @click="toggle(g)">
            <span>{{ g.title }}</span>
            <span v-if="!isOpen(g) && hitungGrup(g)" class="dot-badge" :title="`${hitungGrup(g)} perlu perhatian`">{{ hitungGrup(g) }}</span>
            <ChevronDown :size="14" class="chev" :class="{ open: isOpen(g) }" />
          </button>
          <div v-show="!g.title || isOpen(g)" class="group-items">
            <RouterLink v-for="m in g.items" :key="m.to" :to="m.to" class="nav-item" :class="{ active: isActive(m.to) }">
              <component :is="m.icon" :size="18" />
              <span class="grow">{{ m.label }}</span>
              <span v-if="hitung(m)" class="nav-badge">{{ hitung(m) }}</span>
            </RouterLink>
          </div>
        </div>
      </nav>

      <div class="side-foot">
        <b>RT 03 / RW 20</b>
        <span>Perum Griya Kreasi Aqilla</span>
        <span>Sukajaya · Cibitung · Bekasi</span>
      </div>
    </aside>

    <div class="main">
      <header class="topbar no-print">
        <button class="btn btn-icon menu-btn" aria-label="Buka menu" @click="ui.sidebarOpen = true"><Menu :size="22" /></button>
        <div class="grow titles">
          <h1>{{ route.meta.title }}</h1>
          <span v-if="route.meta.subtitle">{{ route.meta.subtitle }}</span>
        </div>

        <button class="btn btn-icon" :title="ui.theme === 'dark' ? 'Mode terang' : 'Mode gelap'" @click="ui.toggleTheme()">
          <component :is="ui.theme === 'dark' ? Sun : Moon" :size="20" />
        </button>

        <div class="user-wrap">
          <button class="user" :aria-expanded="userMenu" @click="userMenu = !userMenu">
            <span class="avatar">{{ inisial(auth.user?.name) }}</span>
            <span class="who">
              <b>{{ auth.user?.name }}</b>
              <small>{{ auth.user?.role_label }}</small>
            </span>
            <ChevronDown :size="16" class="muted" />
          </button>
          <div v-if="userMenu" class="menu-scrim" @click="userMenu = false" />
          <div v-if="userMenu" class="dropdown">
            <button @click="showPassword = true; userMenu = false"><KeyRound :size="16" /> Ubah kata sandi</button>
            <button class="danger" @click="logout"><LogOut :size="16" /> Keluar</button>
          </div>
        </div>
      </header>

      <main class="content">
        <RouterView v-slot="{ Component, route: r }">
          <component :is="Component" :key="r.path" />
        </RouterView>
      </main>
    </div>

    <ChangePasswordModal v-if="showPassword" @close="showPassword = false" />
  </div>
</template>

<style scoped>
.shell { min-height: 100vh; }
.sidebar {
  position: fixed; inset: 0 auto 0 0; width: var(--sidebar); background: var(--surface); border-right: 1px solid var(--line);
  display: flex; flex-direction: column; padding: 18px 14px; overflow-y: auto; z-index: 60; transition: transform .25s;
}
.brand { display: flex; align-items: center; gap: 12px; padding: 4px 8px 18px; }
.logo { width: 42px; height: 42px; border-radius: 13px; background: var(--primary); color: #fff; display: grid; place-items: center; }
:root[data-theme='dark'] .logo { color: #05211c; }
.brand b { display: block; font-size: 17px; letter-spacing: .04em; }
.brand span { font-size: 12px; color: var(--muted); }
.close-side { display: none; }
.group { margin-bottom: 6px; }
.group-items { display: flex; flex-direction: column; gap: 2px; margin-bottom: 8px; }
.group-title { display: flex; align-items: center; justify-content: space-between; width: 100%; border: 0; background: transparent; font-size: 11px; font-weight: 700; color: var(--faint); text-transform: uppercase; letter-spacing: .08em; padding: 8px 12px; border-radius: 8px; }
.group-title:hover { background: var(--surface-2); color: var(--text); }
.dot-badge { margin-left: auto; margin-right: 6px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 99px; background: var(--accent); color: #fff; font-size: 11px; font-weight: 700; display: grid; place-items: center; letter-spacing: 0; }
.nav-badge { min-width: 22px; height: 20px; padding: 0 6px; border-radius: 99px; background: var(--accent); color: #fff; font-size: 11.5px; font-weight: 700; display: grid; place-items: center; }
.chev { transition: transform .2s; transform: rotate(-90deg); }
.chev.open { transform: none; }
.nav-item {
  display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 10px; color: var(--muted);
  font-weight: 600; font-size: 14px; text-decoration: none; transition: background .15s, color .15s;
}
.nav-item:hover { background: var(--surface-2); color: var(--text); text-decoration: none; }
.nav-item.active { background: var(--primary-soft); color: var(--primary-strong); }
.side-foot { margin-top: auto; padding: 14px; border-radius: 12px; background: var(--surface-2); border: 1px solid var(--line); font-size: 12px; color: var(--muted); display: flex; flex-direction: column; }
.side-foot b { color: var(--text); font-size: 13px; }

.main { margin-left: var(--sidebar); min-width: 0; }
.topbar {
  position: sticky; top: 0; z-index: 40; height: 68px; display: flex; align-items: center; gap: 10px; padding: 0 28px;
  background: color-mix(in srgb, var(--bg) 88%, transparent); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line);
}
.titles h1 { font-size: 16px; font-weight: 700; line-height: 1.2; }
.titles span { font-size: 12.5px; color: var(--muted); }
.menu-btn { display: none; }
.user-wrap { position: relative; }
.user { display: flex; align-items: center; gap: 10px; background: transparent; border: 0; padding: 5px 8px; border-radius: 12px; text-align: left; }
.user:hover { background: var(--surface-2); }
.who b { display: block; font-size: 13px; line-height: 1.2; }
.who small { color: var(--muted); font-size: 11.5px; }
.menu-scrim { position: fixed; inset: 0; z-index: 45; }
.dropdown { position: absolute; right: 0; top: calc(100% + 6px); z-index: 50; min-width: 210px; padding: 6px; background: var(--surface); border: 1px solid var(--line); border-radius: 12px; box-shadow: var(--shadow-lg); }
.dropdown button { display: flex; align-items: center; gap: 10px; width: 100%; border: 0; background: transparent; padding: 10px 12px; border-radius: 8px; font-weight: 600; font-size: 13.5px; text-align: left; }
.dropdown button:hover { background: var(--surface-2); }
.dropdown button.danger { color: var(--bad); }
.content { padding: 28px; max-width: 1400px; margin: 0 auto; }
.scrim { position: fixed; inset: 0; background: rgba(8, 22, 20, .5); z-index: 55; }

@media (max-width: 960px) {
  .sidebar { transform: translateX(-100%); box-shadow: var(--shadow-lg); }
  .sidebar.open { transform: none; }
  .close-side { display: inline-flex; }
  .main { margin-left: 0; }
  .menu-btn { display: inline-flex; }
  .topbar { padding: 0 14px; }
  .content { padding: 18px 14px 32px; }
  .who { display: none; }
  .titles span { display: none; }
}
</style>
