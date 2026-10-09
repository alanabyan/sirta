<script setup>
import { onMounted, ref } from 'vue'
import { PartyPopper, ArrowRight, Paperclip } from 'lucide-vue-next'
import api, { errorMessage } from '@/api'
import { formatTanggal, waktuRelatif } from '@/utils'
import PageHeader from '@/components/PageHeader.vue'
import PengajuanDetailModal from '@/components/PengajuanDetailModal.vue'
import StatusBadge from '@/components/StatusBadge.vue'

const menunggu = ref([])
const diproses = ref([])
const loading = ref(true)
const error = ref('')
const detailId = ref(null)

async function load() {
  try {
    const [a, b] = await Promise.all([
      api.get('/pengajuan', { params: { all: 1, status: 'Menunggu Verifikasi' } }),
      api.get('/pengajuan', { params: { all: 1, status: 'Diproses' } }),
    ])
    // Antrean: yang paling lama menunggu tampil di atas.
    menunggu.value = a.data.data.slice().reverse()
    diproses.value = b.data.data.slice().reverse()
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}
onMounted(load)
</script>

<template>
  <PageHeader title="Verifikasi Permohonan" description="Periksa permohonan yang masuk. Yang paling lama menunggu tampil paling atas.">
    <RouterLink class="btn btn-secondary" to="/layanan">Semua permohonan</RouterLink>
  </PageHeader>

  <div v-if="loading" class="loading"><div class="spinner" /></div>
  <div v-else-if="error" class="callout tone-bad">{{ error }}</div>
  <div v-else class="stack" style="gap:28px">
    <section>
      <h3 class="sec">Menunggu verifikasi <span class="badge plain tone-warn">{{ menunggu.length }}</span></h3>
      <div v-if="!menunggu.length" class="card card-pad done">
        <PartyPopper :size="26" />
        <div><b>Tidak ada antrean.</b><p class="muted small">Semua permohonan sudah diperiksa. Kerja bagus!</p></div>
      </div>
      <div v-else class="grid grid-3">
        <button v-for="p in menunggu" :key="p.id" class="card item" @click="detailId = p.id">
          <div class="row between"><b class="mono">{{ p.kode }}</b><StatusBadge :status="p.status" /></div>
          <div class="who">{{ p.warga?.nama }} <span v-if="p.sumber === 'mandiri'" class="badge plain tone-violet" title="Diajukan sendiri oleh warga">Mandiri</span></div>
          <div class="muted small">{{ p.layanan }}<span v-if="p.lampirans_count" class="clip"> · <Paperclip :size="13" /> {{ p.lampirans_count }} lampiran</span></div>
          <p class="need">{{ p.keperluan || 'Keperluan tidak dijelaskan' }}</p>
          <div class="row between small muted"><span>{{ waktuRelatif(p.created_at) }}</span><span class="go">Periksa <ArrowRight :size="15" /></span></div>
        </button>
      </div>
    </section>

    <section>
      <h3 class="sec">Sedang diproses <span class="badge plain tone-info">{{ diproses.length }}</span></h3>
      <p v-if="!diproses.length" class="muted">Tidak ada permohonan yang sedang diproses.</p>
      <div v-else class="grid grid-3">
        <button v-for="p in diproses" :key="p.id" class="card item" @click="detailId = p.id">
          <div class="row between"><b class="mono">{{ p.kode }}</b><StatusBadge :status="p.status" /></div>
          <div class="who">{{ p.warga?.nama }}</div>
          <div class="muted small">{{ p.layanan }} · {{ formatTanggal(p.created_at, true) }}</div>
          <div class="progress" style="margin:8px 0 4px"><i :style="{ width: p.progress + '%' }" /></div>
          <div class="row between small muted"><span>Menunggu surat selesai</span><span class="go">Buka <ArrowRight :size="15" /></span></div>
        </button>
      </div>
    </section>
  </div>

  <PengajuanDetailModal v-if="detailId" :id="detailId" @close="detailId = null" @changed="load" />
</template>

<style scoped>
.clip { color: var(--info); font-weight: 600; display: inline-flex; align-items: center; gap: 3px; }
.sec { font-size: 16px; margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
.item { text-align: left; padding: 18px; display: flex; flex-direction: column; gap: 4px; cursor: pointer; transition: transform .12s, border-color .12s; width: 100%; }
.item:hover { transform: translateY(-2px); border-color: var(--primary); }
.who { font-size: 16px; font-weight: 700; margin-top: 8px; }
.need { margin: 6px 0 10px; color: var(--text); background: var(--surface-2); padding: 8px 10px; border-radius: 8px; font-size: 13px; min-height: 52px; }
.go { color: var(--primary); font-weight: 650; display: inline-flex; align-items: center; gap: 4px; }
.done { display: flex; align-items: center; gap: 14px; color: var(--ok); background: var(--ok-soft); }
.done b { color: var(--text); }
</style>
