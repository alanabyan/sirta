<script setup>
import { onMounted, ref } from 'vue'
import { Search, Link2 } from 'lucide-vue-next'
import api, { errorMessage } from '@/api'
import { useUi } from '@/stores/ui'
import TrackingTimeline from '@/components/TrackingTimeline.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { formatTanggal } from '@/utils'

const ui = useUi()
const kode = ref('')
const hasil = ref(null)
const recent = ref([])
const error = ref('')
const loading = ref(false)

async function cari(k = kode.value) {
  k = k.trim().toUpperCase()
  if (!k) return
  kode.value = k
  loading.value = true
  error.value = ''
  hasil.value = null
  try {
    const { data: cari } = await api.get('/pengajuan', { params: { q: k, per_page: 5 } })
    const ketemu = cari.data.find((p) => p.kode === k)
    if (!ketemu) {
      error.value = `Kode “${k}” tidak ditemukan.`
      return
    }
    const p = (await api.get(`/pengajuan/${ketemu.id}`)).data.data
    hasil.value = { ...p, pemohon: p.warga.nama }
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

async function salinTautan() {
  const url = `${location.origin}/lacak/${hasil.value.kode}`
  try {
    await navigator.clipboard.writeText(url)
    ui.success('Tautan disalin. Warga akan diminta 4 digit terakhir NIK-nya.')
  } catch {
    ui.error(url)
  }
}

onMounted(async () => {
  recent.value = (await api.get('/pengajuan', { params: { per_page: 6 } })).data.data
})
</script>

<template>
  <PageHeader title="Lacak Permohonan" description="Cari berdasarkan kode untuk melihat tahap dan riwayatnya. Warga juga bisa melacak sendiri tanpa masuk." />

  <div class="grid grid-main">
    <div class="stack">
      <form class="card card-pad row" @submit.prevent="cari()">
        <div class="search"><Search :size="18" /><input v-model="kode" class="input" placeholder="Masukkan kode, mis. PL-2026-0042" aria-label="Kode pengajuan"></div>
        <button class="btn btn-primary" :disabled="loading || !kode.trim()">{{ loading ? 'Mencari…' : 'Lacak' }}</button>
      </form>

      <div v-if="error" class="callout tone-bad" role="alert">{{ error }}</div>
      <div v-if="hasil" class="card card-pad">
        <TrackingTimeline :data="hasil" />
        <hr class="divider">
        <button class="btn btn-secondary btn-sm" @click="salinTautan"><Link2 :size="15" /> Salin tautan untuk warga</button>
      </div>
      <div v-if="!hasil && !error" class="card card-pad muted">Hasil pelacakan akan tampil di sini.</div>
    </div>

    <div class="card">
      <div class="card-head"><div><h3>Permohonan terbaru</h3><p>Klik untuk melacak.</p></div></div>
      <ul class="rec card-body">
        <li v-for="p in recent" :key="p.id">
          <button @click="cari(p.kode)">
            <span class="grow"><b class="mono">{{ p.kode }}</b><small>{{ p.warga?.nama }} · {{ formatTanggal(p.created_at, true) }}</small></span>
            <StatusBadge :status="p.status" />
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.rec { list-style: none; margin: 0; display: flex; flex-direction: column; gap: 6px; }
.rec button { width: 100%; display: flex; align-items: center; gap: 10px; text-align: left; padding: 10px 12px; border: 1px solid var(--line); background: var(--surface); border-radius: 10px; }
.rec button:hover { border-color: var(--primary); background: var(--primary-soft); }
.rec small { display: block; color: var(--muted); font-size: 12px; }
</style>
