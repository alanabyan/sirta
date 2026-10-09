<script setup>
import { computed } from 'vue'
import { Check, X, Clock } from 'lucide-vue-next'
import { formatWaktu } from '@/utils'
import StatusBadge from './StatusBadge.vue'

/** data: { kode, layanan, status, progress, pemohon|warga, riwayat:[{status,catatan,waktu|created_at}] } */
const props = defineProps({ data: Object })

const LANGKAH = [
  { key: 'Menunggu Verifikasi', label: 'Diajukan', desc: 'Permohonan diterima' },
  { key: 'Diproses', label: 'Diproses', desc: 'Data diperiksa & surat disiapkan' },
  { key: 'Selesai', label: 'Selesai', desc: 'Surat siap diambil' },
]
const urutan = ['Menunggu Verifikasi', 'Diproses', 'Selesai']
const ditolak = computed(() => props.data.status === 'Ditolak')
const idx = computed(() => (ditolak.value ? -1 : urutan.indexOf(props.data.status)))

const riwayat = computed(() =>
  (props.data.riwayat || []).map((r) => ({ ...r, waktu: r.waktu || r.created_at })).slice().sort((a, b) => new Date(b.waktu) - new Date(a.waktu)),
)
const state = (i) => (ditolak.value ? 'idle' : i < idx.value ? 'done' : i === idx.value ? (props.data.status === 'Selesai' ? 'done' : 'now') : 'idle')

const pesan = computed(() => ({
  'Menunggu Verifikasi': 'Permohonan sudah masuk dan menunggu diperiksa oleh pengurus RT.',
  Diproses: 'Data sudah diverifikasi. Surat sedang disiapkan oleh pengurus.',
  Selesai: 'Surat sudah selesai. Silakan ambil di sekretariat RT.',
  Ditolak: 'Permohonan belum dapat diproses. Lihat alasan di bawah dan ajukan ulang bila perlu.',
}[props.data.status]))
</script>

<template>
  <div class="trk">
    <div class="row between row-wrap">
      <div>
        <div class="bold" style="font-size:18px">{{ data.kode }}</div>
        <div class="muted">{{ data.layanan }} · {{ data.pemohon || data.warga?.nama }}</div>
      </div>
      <StatusBadge :status="data.status" />
    </div>

    <div class="steps" :class="{ rejected: ditolak }">
      <template v-if="!ditolak">
        <div v-for="(s, i) in LANGKAH" :key="s.key" class="step" :class="state(i)">
          <div class="dot"><Check v-if="state(i) === 'done'" :size="16" /><Clock v-else-if="state(i) === 'now'" :size="15" /><span v-else>{{ i + 1 }}</span></div>
          <b>{{ s.label }}</b>
          <small>{{ s.desc }}</small>
        </div>
      </template>
      <div v-else class="step bad">
        <div class="dot"><X :size="16" /></div>
        <b>Ditolak</b>
        <small>Permohonan tidak dapat diproses</small>
      </div>
    </div>

    <div class="callout" :class="ditolak ? 'tone-bad' : data.status === 'Selesai' ? 'tone-ok' : 'tone-info'">
      <span>{{ pesan }}<template v-if="ditolak && (data.catatan)"><br><b>Alasan:</b> {{ data.catatan }}</template></span>
    </div>

    <div v-if="riwayat.length">
      <h4 class="hist-title">Riwayat</h4>
      <ol class="hist">
        <li v-for="(r, i) in riwayat" :key="i">
          <span class="pt" />
          <div>
            <b>{{ r.status }}</b> <span class="faint small">· {{ formatWaktu(r.waktu) }}</span>
            <p v-if="r.catatan" class="muted small">{{ r.catatan }}</p>
          </div>
        </li>
      </ol>
    </div>
  </div>
</template>

<style scoped>
.trk { display: flex; flex-direction: column; gap: 20px; }
.steps { display: grid; grid-template-columns: repeat(3, 1fr); position: relative; }
.steps.rejected { grid-template-columns: 1fr; }
.step { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 4px; position: relative; padding: 0 6px; }
.step::before { content: ''; position: absolute; top: 17px; right: 50%; width: 100%; height: 3px; background: var(--line); z-index: 0; }
.step:first-child::before, .steps.rejected .step::before { display: none; }
.step.done::before, .step.now::before { background: var(--primary); }
.dot { width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center; background: var(--surface); border: 2px solid var(--line); color: var(--faint); font-weight: 700; position: relative; z-index: 1; }
.step.done .dot { background: var(--primary); border-color: var(--primary); color: #fff; }
.step.now .dot { border-color: var(--primary); color: var(--primary); box-shadow: 0 0 0 5px var(--primary-soft); }
.step.bad .dot { background: var(--bad); border-color: var(--bad); color: #fff; }
.step b { font-size: 13.5px; margin-top: 4px; }
.step small { color: var(--muted); font-size: 12px; }
.step.idle b { color: var(--faint); }
.hist-title { font-size: 13px; margin-bottom: 10px; color: var(--muted); }
.hist { list-style: none; margin: 0; padding: 0 0 0 4px; display: flex; flex-direction: column; gap: 14px; }
.hist li { display: flex; gap: 12px; position: relative; }
.hist li:not(:last-child)::before { content: ''; position: absolute; left: 4px; top: 14px; bottom: -16px; width: 2px; background: var(--line); }
.pt { width: 10px; height: 10px; border-radius: 50%; background: var(--primary); margin-top: 6px; flex: none; }
</style>
