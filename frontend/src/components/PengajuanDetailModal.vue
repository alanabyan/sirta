<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Check, X, FileCheck2, Send, Paperclip, Eye, Download, FileText, Image } from 'lucide-vue-next'
import api, { errorMessage } from '@/api'
import { useAuth } from '@/stores/auth'
import { useUi } from '@/stores/ui'
import { formatTanggal, formatUkuran } from '@/utils'
import BaseModal from './BaseModal.vue'
import TrackingTimeline from './TrackingTimeline.vue'

const props = defineProps({ id: Number })
const emit = defineEmits(['close', 'changed'])

const auth = useAuth()
const ui = useUi()
const router = useRouter()
const p = ref(null)
const loading = ref(true)
const busy = ref(false)
const menolak = ref(false)
const alasan = ref('')
const catatan = ref('')

async function load() {
  try {
    p.value = (await api.get(`/pengajuan/${props.id}`)).data.data
  } catch (e) {
    ui.error(errorMessage(e))
    emit('close')
  } finally {
    loading.value = false
  }
}

async function ubah(status, note) {
  busy.value = true
  try {
    const { data } = await api.patch(`/pengajuan/${props.id}/status`, { status, catatan: note || null })
    ui.success(data.message)
    menolak.value = false
    alasan.value = catatan.value = ''
    await load()
    emit('changed')
  } catch (e) {
    ui.error(errorMessage(e))
  } finally {
    busy.value = false
  }
}

const bisaPutuskan = computed(() => auth.canDecide && p.value && ['Menunggu Verifikasi', 'Diproses'].includes(p.value.status))

// Lampiran bersifat privat: diambil lewat API ber-login sebagai blob, tidak lewat tautan langsung.
const pratinjau = ref(null) // { nama, mime, url }
async function ambilLampiran(l) {
  const { data } = await api.get(`/pengajuan/${props.id}/lampiran/${l.id}`, { responseType: 'blob' })
  return URL.createObjectURL(data)
}
async function lihat(l) {
  try {
    pratinjau.value = { nama: l.nama, mime: l.mime, url: await ambilLampiran(l) }
  } catch (e) {
    ui.error(e.response?.status === 404 ? 'Berkas tidak ditemukan di server.' : errorMessage(e))
  }
}
function tutupPratinjau() {
  if (pratinjau.value) URL.revokeObjectURL(pratinjau.value.url)
  pratinjau.value = null
}
async function unduhLampiran(l) {
  try {
    const url = await ambilLampiran(l)
    const el = Object.assign(document.createElement('a'), { href: url, download: l.nama })
    document.body.appendChild(el)
    el.click()
    el.remove()
    setTimeout(() => URL.revokeObjectURL(url), 10000)
  } catch (e) {
    ui.error(errorMessage(e))
  }
}

function buatSurat() {
  router.push({ path: '/surat-keluar', query: { pengajuan: p.value.id } })
}

onMounted(load)
</script>

<template>
  <BaseModal :title="p ? `Permohonan ${p.kode}` : 'Memuat…'" size="lg" hide-footer @close="emit('close')">
    <div v-if="loading" class="loading"><div class="spinner" /></div>
    <div v-else-if="p" class="stack" style="gap:20px">
      <dl class="kv card card-pad" style="box-shadow:none;background:var(--surface-2)">
        <dt>Pemohon</dt><dd>{{ p.warga.nama }}</dd>
        <dt>NIK</dt><dd class="mono">{{ p.warga.nik_samar }}</dd>
        <dt>Alamat</dt><dd>{{ p.warga.keluarga?.alamat || '— (warga belum terhubung ke KK)' }}</dd>
        <dt>Layanan</dt><dd>{{ p.layanan }}</dd>
        <dt>Keperluan</dt><dd>{{ p.keperluan || '—' }}</dd>
        <dt>Diajukan</dt><dd>{{ formatTanggal(p.created_at) }}</dd>
      </dl>

      <div v-if="p.lampirans?.length" class="lamp">
        <h4><Paperclip :size="16" /> Lampiran dari pemohon ({{ p.lampirans.length }})</h4>
        <ul>
          <li v-for="l in p.lampirans" :key="l.id">
            <component :is="l.mime === 'application/pdf' ? FileText : Image" :size="20" />
            <span class="grow truncate">{{ l.nama }}</span>
            <small class="muted">{{ formatUkuran(l.ukuran) }}</small>
            <button type="button" class="btn btn-soft btn-sm" @click="lihat(l)"><Eye :size="15" /> Lihat</button>
            <button type="button" class="btn btn-icon" title="Unduh" :aria-label="`Unduh ${l.nama}`" @click="unduhLampiran(l)"><Download :size="17" /></button>
          </li>
        </ul>
      </div>
      <p v-else-if="p.sumber === 'mandiri'" class="muted small"><Paperclip :size="14" style="vertical-align:-2px" /> Pemohon tidak melampirkan berkas.</p>

      <TrackingTimeline :data="{ ...p, riwayat: p.riwayat.map((r) => ({ ...r, catatan: r.catatan ? r.catatan + (r.user ? ` — ${r.user.name}` : '') : (r.user ? `oleh ${r.user.name}` : '') })) }" />

      <!-- Tindakan -->
      <div v-if="bisaPutuskan" class="actions-box">
        <template v-if="!menolak">
          <div v-if="p.status === 'Menunggu Verifikasi'">
            <h4>Periksa lalu putuskan</h4>
            <p class="muted small">Pastikan data pemohon benar dan keperluannya jelas sebelum menyetujui.</p>
            <div class="row row-wrap" style="margin-top:12px">
              <button class="btn btn-primary" :disabled="busy" @click="ubah('Diproses')"><Check :size="17" /> Setujui &amp; proses</button>
              <button class="btn btn-danger" :disabled="busy" @click="menolak = true"><X :size="17" /> Tolak…</button>
            </div>
          </div>
          <div v-else>
            <h4>Sedang diproses</h4>
            <p class="muted small">Siapkan suratnya, lalu tandai selesai agar warga tahu surat sudah bisa diambil.</p>
            <div class="row row-wrap" style="margin-top:12px">
              <button class="btn btn-secondary" @click="buatSurat"><Send :size="16" /> Buat surat</button>
              <button class="btn btn-primary" :disabled="busy" @click="ubah('Selesai', 'Surat dapat diambil di sekretariat RT.')"><FileCheck2 :size="17" /> Tandai selesai</button>
              <button class="btn btn-danger" :disabled="busy" @click="menolak = true"><X :size="17" /> Batalkan…</button>
            </div>
          </div>
        </template>
        <form v-else @submit.prevent="ubah('Ditolak', alasan)">
          <h4>Alasan penolakan</h4>
          <p class="muted small">Alasan ini akan terlihat oleh warga saat melacak permohonannya.</p>
          <textarea v-model="alasan" class="textarea" style="margin-top:10px;min-height:80px" placeholder="mis. Fotokopi KK belum dilampirkan" required autofocus />
          <div class="row" style="margin-top:12px">
            <button type="submit" class="btn btn-solid-danger" :disabled="busy || !alasan.trim()">Tolak permohonan</button>
            <button type="button" class="btn btn-secondary" @click="menolak = false">Kembali</button>
          </div>
        </form>
      </div>
      <div v-else-if="p.status === 'Selesai' && auth.canWrite" class="actions-box">
        <h4>Permohonan selesai</h4>
        <p class="muted small">Belum membuat suratnya? Buat dari template dengan data pemohon terisi otomatis.</p>
        <button class="btn btn-secondary" style="margin-top:12px" @click="buatSurat"><Send :size="16" /> Buat surat keluar</button>
      </div>
    </div>
  </BaseModal>

  <BaseModal v-if="pratinjau" :title="pratinjau.nama" size="lg" hide-footer @close="tutupPratinjau">
    <img v-if="pratinjau.mime.startsWith('image/')" :src="pratinjau.url" :alt="pratinjau.nama" class="prev-img">
    <iframe v-else :src="pratinjau.url" :title="pratinjau.nama" class="prev-pdf" />
  </BaseModal>
</template>

<style scoped>
.actions-box { border: 1px solid var(--line); border-radius: 14px; padding: 16px 18px; background: var(--surface); }
.actions-box h4 { font-size: 14.5px; }
.lamp { border: 1px solid var(--line); border-radius: 14px; padding: 14px 16px; }
.lamp h4 { font-size: 14px; display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.lamp ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.lamp li { display: flex; align-items: center; gap: 10px; padding: 6px 6px 6px 12px; background: var(--surface-2); border-radius: 10px; }
.prev-img { max-width: 100%; display: block; margin: 0 auto; border-radius: 8px; }
.prev-pdf { width: 100%; height: 70vh; border: 0; border-radius: 8px; background: #fff; }
</style>
