<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Check, X, FileCheck2, Send, Paperclip, Eye, Download, FileText, Image } from 'lucide-vue-next'
import api, { errorMessage } from '@/api'
import { useAuth } from '@/stores/auth'
import { useUi } from '@/stores/ui'
import { useAntrean } from '@/stores/antrean'
import { formatTanggal, formatUkuran } from '@/utils'
import BaseModal from './BaseModal.vue'
import TrackingTimeline from './TrackingTimeline.vue'

const props = defineProps({ id: Number })
const emit = defineEmits(['close', 'changed'])

const auth = useAuth()
const ui = useUi()
const antrean = useAntrean()
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
    antrean.refresh()
  } catch (e) {
    ui.error(errorMessage(e))
  } finally {
    busy.value = false
  }
}

const alasanEl = ref(null)
async function mulaiTolak() {
  menolak.value = true
  await nextTick()
  alasanEl.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  alasanEl.value?.focus()
}

const bisaPutuskan = computed(() => auth.canDecide && p.value && ['Menunggu Verifikasi', 'Diproses'].includes(p.value.status))
const bisaBuatSurat = computed(() => auth.canWrite && p.value?.status === 'Selesai')
const adaFooter = computed(() => !!p.value && (bisaPutuskan.value || bisaBuatSurat.value))

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
  <BaseModal :title="p ? `Permohonan ${p.kode}` : 'Memuat…'" size="lg" :hide-footer="!adaFooter" @close="emit('close')">
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

      <div v-if="menolak" class="tolak">
        <h4>Alasan penolakan</h4>
        <p class="muted small">Alasan ini akan terlihat oleh warga saat melacak permohonannya.</p>
        <textarea ref="alasanEl" v-model="alasan" class="textarea" style="margin-top:10px;min-height:80px" placeholder="mis. Fotokopi KK belum dilampirkan" />
      </div>
    </div>

    <template #footer>
      <template v-if="menolak">
        <button type="button" class="btn btn-secondary" @click="menolak = false">Kembali</button>
        <button type="button" class="btn btn-solid-danger" :disabled="busy || !alasan.trim()" @click="ubah('Ditolak', alasan)">Tolak permohonan</button>
      </template>
      <template v-else-if="bisaPutuskan && p.status === 'Menunggu Verifikasi'">
        <span class="hint grow">Periksa data dan lampiran sebelum menyetujui.</span>
        <button type="button" class="btn btn-danger" :disabled="busy" @click="mulaiTolak"><X :size="17" /> Tolak…</button>
        <button type="button" class="btn btn-primary" :disabled="busy" @click="ubah('Diproses')"><Check :size="17" /> Setujui &amp; proses</button>
      </template>
      <template v-else-if="bisaPutuskan">
        <span class="hint grow">Siapkan surat, lalu tandai selesai agar warga tahu.</span>
        <button type="button" class="btn btn-danger" :disabled="busy" @click="mulaiTolak"><X :size="17" /> Batalkan…</button>
        <button type="button" class="btn btn-secondary" @click="buatSurat"><Send :size="16" /> Buat surat</button>
        <button type="button" class="btn btn-primary" :disabled="busy" @click="ubah('Selesai', 'Surat dapat diambil di sekretariat RT.')"><FileCheck2 :size="17" /> Tandai selesai</button>
      </template>
      <template v-else-if="bisaBuatSurat">
        <span class="hint grow">Permohonan selesai. Surat belum dibuat?</span>
        <button type="button" class="btn btn-primary" @click="buatSurat"><Send :size="16" /> Buat surat keluar</button>
      </template>
    </template>
  </BaseModal>

  <BaseModal v-if="pratinjau" :title="pratinjau.nama" size="lg" hide-footer @close="tutupPratinjau">
    <img v-if="pratinjau.mime.startsWith('image/')" :src="pratinjau.url" :alt="pratinjau.nama" class="prev-img">
    <iframe v-else :src="pratinjau.url" :title="pratinjau.nama" class="prev-pdf" />
  </BaseModal>
</template>

<style scoped>
.tolak { border: 1px solid var(--bad); border-radius: 14px; padding: 16px 18px; background: var(--bad-soft); }
.tolak h4 { font-size: 14.5px; }
.hint { color: var(--muted); font-size: 13px; align-self: center; }
@media (max-width: 640px) { .hint { display: none; } }
.lamp { border: 1px solid var(--line); border-radius: 14px; padding: 14px 16px; }
.lamp h4 { font-size: 14px; display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.lamp ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.lamp li { display: flex; align-items: center; gap: 10px; padding: 6px 6px 6px 12px; background: var(--surface-2); border-radius: 10px; }
.prev-img { max-width: 100%; display: block; margin: 0 auto; border-radius: 8px; }
.prev-pdf { width: 100%; height: 70vh; border: 0; border-radius: 8px; background: #fff; }
</style>
