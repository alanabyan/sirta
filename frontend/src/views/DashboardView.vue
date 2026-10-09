<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  Users, UsersRound, Clock3, Archive, ClipboardCheck, Inbox, PartyPopper, Plus, ArrowRight,
  FilePlus2, UserPlus, Send, Check, Pencil, Trash2, LogIn, Info, X,
} from 'lucide-vue-next'
import api, { errorMessage } from '@/api'
import { useAuth } from '@/stores/auth'
import { formatTanggal, sapaan, waktuRelatif } from '@/utils'
import StatusBadge from '@/components/StatusBadge.vue'

const auth = useAuth()
const router = useRouter()
const data = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    data.value = (await api.get('/dashboard')).data.data
  } catch (e) {
    error.value = errorMessage(e)
  }
})

const tanggal = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date())

const maxBulan = computed(() => Math.max(5, ...(data.value?.per_bulan.map((b) => b.total) ?? [0])))

const STATUS_WARNA = {
  'Menunggu Verifikasi': 'var(--accent)',
  Diproses: 'var(--info)',
  Selesai: 'var(--ok)',
  Ditolak: 'var(--bad)',
}
const statusTotal = computed(() => Object.values(data.value?.status ?? {}).reduce((a, b) => a + b, 0) || 1)

const ikonAktivitas = { plus: Plus, edit: Pencil, trash: Trash2, check: Check, x: X, archive: Archive, login: LogIn, info: Info }

const aksiCepat = computed(() => {
  if (!auth.canWrite) return []
  return [
    { label: 'Buat permohonan', icon: FilePlus2, to: '/layanan?baru=1' },
    { label: 'Tambah warga', icon: UserPlus, to: '/warga?baru=1' },
    { label: 'Buat surat keluar', icon: Send, to: '/surat-keluar?baru=1' },
    { label: 'Catat surat masuk', icon: Inbox, to: '/surat-masuk?baru=1' },
  ]
})
</script>

<template>
  <div v-if="error" class="callout tone-bad">{{ error }}</div>
  <div v-else-if="!data" class="loading"><div class="spinner" /></div>

  <div v-else class="stack" style="gap:20px">
    <section class="welcome">
      <div>
        <span class="date">{{ tanggal }}</span>
        <h2>{{ sapaan() }}, {{ auth.user.name }} 👋</h2>
        <p>Berikut gambaran singkat administrasi RT 03 / RW 20 hari ini.</p>
      </div>
      <div v-if="aksiCepat.length" class="quick">
        <button v-for="a in aksiCepat" :key="a.label" @click="router.push(a.to)">
          <component :is="a.icon" :size="18" /> {{ a.label }}
        </button>
      </div>
    </section>

    <!-- Perlu tindakan -->
    <section class="card todo">
      <div class="card-head" style="padding-bottom:6px">
        <div><h3>Perlu perhatian Anda</h3><p>Hal-hal yang sebaiknya segera ditindaklanjuti.</p></div>
      </div>
      <div class="card-body todo-list">
        <template v-if="data.kpi.perlu_verifikasi || data.kpi.surat_baru">
          <RouterLink v-if="data.kpi.perlu_verifikasi" to="/verifikasi" class="todo-item amber">
            <ClipboardCheck :size="22" />
            <span class="grow"><b>{{ data.kpi.perlu_verifikasi }} permohonan menunggu verifikasi</b><small>Warga menunggu jawaban. Periksa dan proses sekarang.</small></span>
            <ArrowRight :size="18" />
          </RouterLink>
          <RouterLink v-if="data.kpi.surat_baru" to="/surat-masuk" class="todo-item blue">
            <Inbox :size="22" />
            <span class="grow"><b>{{ data.kpi.surat_baru }} surat masuk baru</b><small>Belum ditindaklanjuti atau diarsipkan.</small></span>
            <ArrowRight :size="18" />
          </RouterLink>
        </template>
        <div v-else class="todo-item green static">
          <PartyPopper :size="22" />
          <span class="grow"><b>Semua beres!</b><small>Tidak ada permohonan atau surat yang menunggu.</small></span>
        </div>
      </div>
    </section>

    <!-- Angka utama -->
    <section class="grid grid-4">
      <RouterLink to="/warga" class="card stat">
        <span class="ic ic-green"><Users :size="22" /></span>
        <div><small>Warga aktif</small><b>{{ data.kpi.warga }}</b></div>
      </RouterLink>
      <RouterLink to="/keluarga" class="card stat">
        <span class="ic ic-blue"><UsersRound :size="22" /></span>
        <div><small>Keluarga (KK)</small><b>{{ data.kpi.keluarga }}</b></div>
      </RouterLink>
      <RouterLink to="/layanan" class="card stat">
        <span class="ic ic-amber"><Clock3 :size="22" /></span>
        <div><small>Permohonan berjalan</small><b>{{ data.kpi.pengajuan_aktif }}</b></div>
      </RouterLink>
      <RouterLink to="/arsip" class="card stat">
        <span class="ic ic-violet"><Archive :size="22" /></span>
        <div><small>Dokumen arsip</small><b>{{ data.kpi.arsip }}</b></div>
      </RouterLink>
    </section>

    <section class="grid grid-main">
      <div class="card">
        <div class="card-head"><div><h3>Permohonan per bulan</h3><p>Jumlah permohonan warga dalam 6 bulan terakhir.</p></div></div>
        <div class="card-body">
          <div class="bars" role="img" aria-label="Grafik batang permohonan per bulan">
            <div v-for="b in data.per_bulan" :key="b.label" class="bar-col">
              <span class="val">{{ b.total }}</span>
              <div class="bar" :style="{ height: (b.total / maxBulan) * 100 + '%' }" :title="`${b.label}: ${b.total} permohonan`" />
              <span class="lbl">{{ b.label }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><div><h3>Status permohonan</h3><p>Seluruh permohonan, berdasarkan tahap.</p></div></div>
        <div class="card-body">
          <div class="stackbar">
            <i v-for="(n, s) in data.status" :key="s" :style="{ width: (n / statusTotal) * 100 + '%', background: STATUS_WARNA[s] }" :title="`${s}: ${n}`" />
          </div>
          <ul class="legend">
            <li v-for="(n, s) in data.status" :key="s">
              <span class="sw" :style="{ background: STATUS_WARNA[s] }" /> <span class="grow">{{ s }}</span> <b>{{ n }}</b>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <section class="grid grid-main">
      <div class="card">
        <div class="card-head">
          <div><h3>Permohonan terbaru</h3></div>
          <RouterLink to="/layanan" class="btn btn-soft btn-sm">Lihat semua</RouterLink>
        </div>
        <div class="table-wrap" style="margin-top:10px">
          <table class="table">
            <thead><tr><th>Kode</th><th>Pemohon</th><th>Layanan</th><th>Status</th></tr></thead>
            <tbody>
              <tr v-for="p in data.terbaru" :key="p.id">
                <td class="mono bold">{{ p.kode }}</td>
                <td>{{ p.warga?.nama }}<div class="cell-sub">{{ formatTanggal(p.created_at, true) }}</div></td>
                <td>{{ p.layanan }}</td>
                <td><StatusBadge :status="p.status" /></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><div><h3>Aktivitas terakhir</h3></div></div>
        <ol class="card-body feed">
          <li v-for="a in data.aktivitas" :key="a.id">
            <span class="fi"><component :is="ikonAktivitas[a.ikon] || Info" :size="15" /></span>
            <div><span>{{ a.deskripsi }}</span><small>{{ a.user?.name ? a.user.name + ' · ' : '' }}{{ waktuRelatif(a.created_at) }}</small></div>
          </li>
          <li v-if="!data.aktivitas.length" class="muted">Belum ada aktivitas.</li>
        </ol>
      </div>
    </section>

    <section class="card card-pad flow">
      <h3>Alur pelayanan surat</h3>
      <p class="muted small">Begini perjalanan permohonan warga di SIRTA.</p>
      <div class="flow-steps">
        <div><b>1</b><span>Warga mengajukan<small>Dicatat pengurus atau lewat bantuan operator</small></span></div>
        <div><b>2</b><span>Pengurus memverifikasi<small>Data diperiksa, disetujui atau ditolak</small></span></div>
        <div><b>3</b><span>Surat diterbitkan<small>Dibuat dari template, nomor otomatis</small></span></div>
        <div><b>4</b><span>Warga melacak<small>Cukup dengan kode pengajuan</small></span></div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.welcome { display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; padding: 26px 28px; border-radius: 20px; color: #fff; background: linear-gradient(120deg, #0a5f53, #0f7b6c 60%, #1a9a87); position: relative; overflow: hidden; }
.welcome::after { content: ''; position: absolute; right: -60px; top: -90px; width: 260px; height: 260px; border-radius: 50%; background: rgba(255,255,255,.08); }
.date { font-size: 12.5px; opacity: .8; }
.welcome h2 { font-size: 24px; margin: 4px 0; letter-spacing: -.01em; }
.welcome p { opacity: .9; }
.quick { display: flex; gap: 8px; flex-wrap: wrap; position: relative; z-index: 1; }
.quick button { display: flex; align-items: center; gap: 8px; border: 1px solid rgba(255,255,255,.3); background: rgba(255,255,255,.14); color: #fff; padding: 9px 14px; border-radius: 10px; font-weight: 600; font-size: 13px; }
.quick button:hover { background: rgba(255,255,255,.25); }

.todo-list { display: flex; flex-direction: column; gap: 10px; }
.todo-item { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border-radius: 12px; text-decoration: none !important; color: var(--text); border: 1px solid transparent; transition: transform .1s; }
.todo-item:not(.static):hover { transform: translateX(3px); }
.todo-item b { display: block; font-size: 14.5px; }
.todo-item small { color: var(--muted); font-size: 12.5px; }
.todo-item.amber { background: var(--warn-soft); } .todo-item.amber > svg:first-child { color: var(--warn); }
.todo-item.blue { background: var(--info-soft); } .todo-item.blue > svg:first-child { color: var(--info); }
.todo-item.green { background: var(--ok-soft); } .todo-item.green > svg:first-child { color: var(--ok); }

.stat { display: flex; align-items: center; gap: 16px; padding: 18px 20px; text-decoration: none !important; color: var(--text); transition: transform .12s, border-color .12s; }
.stat:hover { transform: translateY(-2px); border-color: var(--primary); }
.stat small { display: block; color: var(--muted); font-size: 12.5px; font-weight: 600; }
.stat b { font-size: 30px; line-height: 1.15; letter-spacing: -.02em; }
.ic { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; flex: none; }
.ic-green { background: var(--primary-soft); color: var(--primary); }
.ic-blue { background: var(--info-soft); color: var(--info); }
.ic-amber { background: var(--warn-soft); color: var(--warn); }
.ic-violet { background: var(--violet-soft); color: var(--violet); }

.bars { height: 230px; display: flex; align-items: stretch; gap: 14px; padding-top: 10px; }
.bar-col { flex: 1; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; gap: 6px; min-width: 0; }
.bar-col .bar { width: min(100%, 46px); background: linear-gradient(180deg, var(--primary), color-mix(in srgb, var(--primary) 60%, transparent)); border-radius: 8px 8px 3px 3px; min-height: 4px; transition: height .5s; }
.bar-col .bar:hover { filter: brightness(1.1); }
.val { font-size: 12.5px; font-weight: 700; }
.lbl { font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); padding-top: 6px; width: 100%; text-align: center; }

.stackbar { display: flex; height: 14px; border-radius: 99px; overflow: hidden; background: var(--line); gap: 2px; }
.stackbar i { display: block; height: 100%; }
.legend { list-style: none; padding: 0; margin: 18px 0 0; display: flex; flex-direction: column; gap: 12px; }
.legend li { display: flex; align-items: center; gap: 10px; }
.sw { width: 12px; height: 12px; border-radius: 4px; }

.feed { list-style: none; margin: 0; display: flex; flex-direction: column; gap: 16px; }
.feed li { display: flex; gap: 12px; }
.fi { width: 30px; height: 30px; border-radius: 10px; background: var(--primary-soft); color: var(--primary); display: grid; place-items: center; flex: none; }
.feed small { display: block; color: var(--faint); font-size: 12px; }

.flow h3 { font-size: 15px; }
.flow-steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 16px; }
.flow-steps > div { display: flex; gap: 12px; align-items: flex-start; }
.flow-steps b { width: 30px; height: 30px; border-radius: 50%; background: var(--primary); color: #fff; display: grid; place-items: center; flex: none; font-size: 13px; }
.flow-steps span { font-weight: 650; font-size: 13.5px; }
.flow-steps small { display: block; font-weight: 400; color: var(--muted); font-size: 12.5px; }
@media (max-width: 900px) { .flow-steps { grid-template-columns: 1fr 1fr; } }
@media (max-width: 560px) { .flow-steps { grid-template-columns: 1fr; } .welcome { padding: 20px; } }
</style>
