<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { Plus, Search, Eye, FilePlus2, Trash2, Paperclip } from 'lucide-vue-next'
import api from '@/api'
import { useAuth } from '@/stores/auth'
import { useList } from '@/composables/useList'
import { useSave } from '@/composables/useSave'
import { formatTanggal } from '@/utils'
import PageHeader from '@/components/PageHeader.vue'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import PengajuanDetailModal from '@/components/PengajuanDetailModal.vue'

const LAYANAN = ['Surat Pengantar', 'Surat Domisili', 'Surat Keterangan', 'Surat Keterangan Usaha', 'Surat Pengantar Nikah']
const TAB = [['', 'Semua'], ['Menunggu Verifikasi', 'Menunggu verifikasi'], ['Diproses', 'Diproses'], ['Selesai', 'Selesai'], ['Ditolak', 'Ditolak']]

const auth = useAuth()
const route = useRoute()
const list = useList('/pengajuan', { filters: { status: '', layanan: '' } })
const { saving, errors, save } = useSave('/pengajuan')

const wargaOpsi = ref([])
const form = reactive({ warga_id: '', layanan: LAYANAN[0], keperluan: '' })
const showForm = ref(false)
const detailId = ref(null)

function buka() {
  Object.assign(form, { warga_id: '', layanan: LAYANAN[0], keperluan: '' })
  errors.value = {}
  showForm.value = true
}
async function simpan() {
  const hasil = await save(null, { ...form })
  if (hasil) {
    showForm.value = false
    list.load()
    detailId.value = hasil.id
  }
}
const hapus = (p) => list.remove(p, { title: `Hapus ${p.kode}?`, message: 'Permohonan beserta riwayatnya akan dihapus permanen.' })

onMounted(async () => {
  wargaOpsi.value = (await api.get('/warga', { params: { all: 1, status: 'Aktif' } })).data.data
  if (route.query.baru && auth.canWrite) buka()
})
</script>

<template>
  <PageHeader title="Permohonan Warga" description="Catat permohonan surat dari warga, lalu pantau perkembangannya hingga selesai.">
    <button v-if="auth.canWrite" class="btn btn-primary" @click="buka"><Plus :size="18" /> Permohonan baru</button>
  </PageHeader>

  <div class="card">
    <div class="tabs" role="tablist">
      <button v-for="t in TAB" :key="t[0]" role="tab" :aria-selected="list.filter.status === t[0]" :class="{ on: list.filter.status === t[0] }" @click="list.filter.status = t[0]">{{ t[1] }}</button>
    </div>
    <div class="toolbar">
      <div class="search"><Search :size="18" /><input v-model="list.q.value" class="input" placeholder="Cari kode atau nama pemohon…" aria-label="Cari permohonan"></div>
      <select v-model="list.filter.layanan" class="select" aria-label="Jenis layanan"><option value="">Semua layanan</option><option v-for="l in LAYANAN" :key="l">{{ l }}</option></select>
    </div>

    <div v-if="list.loading.value && !list.items.value.length" class="loading"><div class="spinner" /></div>
    <div v-else-if="list.error.value" class="callout tone-bad" style="margin:16px">{{ list.error.value }}</div>
    <EmptyState v-else-if="!list.items.value.length" :icon="FilePlus2" title="Tidak ada permohonan" text="Belum ada permohonan pada filter ini." />
    <div v-else class="table-wrap">
      <table class="table">
        <thead><tr><th>Kode</th><th>Pemohon</th><th>Layanan</th><th>Diajukan</th><th>Status</th><th /></tr></thead>
        <tbody>
          <tr v-for="p in list.items.value" :key="p.id">
            <td class="cell-title mono">{{ p.kode }} <span v-if="p.sumber === 'mandiri'" class="badge plain tone-violet" title="Diajukan sendiri oleh warga">Mandiri</span> <span v-if="p.lampirans_count" class="clip" :title="`${p.lampirans_count} lampiran`"><Paperclip :size="14" />{{ p.lampirans_count }}</span></td>
            <td>{{ p.warga?.nama }}</td>
            <td>{{ p.layanan }}</td>
            <td>{{ formatTanggal(p.created_at, true) }}</td>
            <td style="min-width:150px"><StatusBadge :status="p.status" /><div class="progress" style="margin-top:7px;height:5px"><i :style="{ width: p.progress + '%', background: p.status === 'Ditolak' ? 'var(--bad)' : '' }" /></div></td>
            <td class="actions">
              <button class="btn btn-soft btn-sm" @click="detailId = p.id"><Eye :size="15" /> Detail</button>
              <button v-if="auth.canDecide" class="btn btn-icon" title="Hapus" @click="hapus(p)"><Trash2 :size="17" /></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar v-bind="list.meta" @change="list.goto" />
  </div>

  <BaseModal v-if="showForm" title="Permohonan baru" subtitle="Setelah disimpan, permohonan masuk antrean verifikasi." size="sm" :saving="saving" save-text="Ajukan" @close="showForm = false" @save="simpan">
    <div class="stack">
      <FormField label="Pemohon" :error="errors.warga_id" hint="Belum terdaftar? Tambahkan dulu di menu Data Warga." required>
        <select v-model="form.warga_id" class="select" required>
          <option value="" disabled>— Pilih warga —</option>
          <option v-for="w in wargaOpsi" :key="w.id" :value="w.id">{{ w.nama }}</option>
        </select>
      </FormField>
      <FormField label="Jenis layanan" :error="errors.layanan" required><select v-model="form.layanan" class="select"><option v-for="l in LAYANAN" :key="l">{{ l }}</option></select></FormField>
      <FormField label="Keperluan" :error="errors.keperluan" hint="Jelaskan untuk apa surat ini dibutuhkan."><textarea v-model="form.keperluan" class="textarea" placeholder="mis. Persyaratan pendaftaran sekolah anak" /></FormField>
    </div>
  </BaseModal>

  <PengajuanDetailModal v-if="detailId" :id="detailId" @close="detailId = null" @changed="list.load()" />
</template>

<style scoped>
.tabs { display: flex; gap: 4px; padding: 12px 16px 0; border-bottom: 1px solid var(--line); overflow-x: auto; }
.tabs button { border: 0; background: transparent; padding: 10px 14px; font-weight: 600; color: var(--muted); border-bottom: 3px solid transparent; margin-bottom: -1px; white-space: nowrap; }
.tabs button:hover { color: var(--text); }
.tabs button.on { color: var(--primary); border-color: var(--primary); }
.toolbar { border-bottom: 1px solid var(--line); }
.clip { display: inline-flex; align-items: center; gap: 2px; color: var(--muted); font-size: 12px; font-weight: 600; vertical-align: middle; }
</style>
