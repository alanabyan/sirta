<script setup>
import { onMounted, onUnmounted, reactive, ref } from 'vue'
import { Save, Info, ShieldCheck } from 'lucide-vue-next'
import api, { errorMessage, fieldErrors } from '@/api'
import { useUi } from '@/stores/ui'
import { resetCacheCetak } from '@/cetak'
import { formatTanggal, hariIni } from '@/utils'
import PageHeader from '@/components/PageHeader.vue'
import FormField from '@/components/FormField.vue'
import GambarSlot from '@/components/GambarSlot.vue'
import TandaTanganPad from '@/components/TandaTanganPad.vue'

const ui = useUi()
const loading = ref(true)
const saving = ref(false)
const errors = ref({})
const form = reactive({ kop: '', kota: '', penandatangan_nama: '', penandatangan_jabatan: '' })
const files = reactive({ tanda_tangan: null, stempel: null })
const hapus = reactive({ tanda_tangan: false, stempel: false })
const preview = reactive({ tanda_tangan: '', stempel: '' }) // blob URL
const showPad = ref(false)

const lepas = (k) => preview[k] && URL.revokeObjectURL(preview[k])

async function muat() {
  try {
    const p = (await api.get('/pengaturan')).data.data
    Object.assign(form, { kop: p.kop, kota: p.kota, penandatangan_nama: p.penandatangan_nama, penandatangan_jabatan: p.penandatangan_jabatan })
    for (const [k, ada] of [['tanda_tangan', p.ada_tanda_tangan], ['stempel', p.ada_stempel]]) {
      lepas(k)
      files[k] = null
      hapus[k] = false
      preview[k] = ada ? URL.createObjectURL((await api.get(`/pengaturan/gambar/${k}`, { responseType: 'blob' })).data) : ''
    }
  } catch (e) {
    ui.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}

function pilih(k, file) {
  if (!/\.(png|jpe?g)$/i.test(file.name) && !/^image\/(png|jpeg)$/.test(file.type)) return ui.error('Gambar harus berformat PNG atau JPG.')
  if (file.size > 1024 * 1024) return ui.error('Ukuran gambar maksimal 1 MB.')
  lepas(k)
  files[k] = file
  hapus[k] = false
  preview[k] = URL.createObjectURL(file)
}
function buang(k) {
  lepas(k)
  files[k] = null
  hapus[k] = true
  preview[k] = ''
}
function dariPad(file) {
  showPad.value = false
  pilih('tanda_tangan', file)
}

async function simpan() {
  saving.value = true
  errors.value = {}
  try {
    const fd = new FormData()
    Object.entries(form).forEach(([k, v]) => fd.append(k, v))
    for (const k of ['tanda_tangan', 'stempel']) {
      if (files[k]) fd.append(k, files[k])
      fd.append(`hapus_${k}`, hapus[k] ? '1' : '0')
    }
    const { data } = await api.post('/pengaturan', fd)
    ui.success(data.message)
    resetCacheCetak()
    await muat()
  } catch (e) {
    errors.value = fieldErrors(e)
    ui.error(Object.keys(errors.value).length ? 'Periksa kembali isian yang ditandai merah.' : errorMessage(e))
  } finally {
    saving.value = false
  }
}

onMounted(muat)
onUnmounted(() => ['tanda_tangan', 'stempel'].forEach(lepas))
</script>

<template>
  <PageHeader title="Identitas & Tanda Tangan" description="Atur kop surat, penandatangan, tanda tangan, dan stempel. Semuanya dipasang otomatis pada surat yang diterbitkan.">
    <button class="btn btn-primary" :disabled="saving || loading" @click="simpan"><Save :size="17" /> {{ saving ? 'Menyimpan…' : 'Simpan pengaturan' }}</button>
  </PageHeader>

  <div v-if="loading" class="loading"><div class="spinner" /></div>
  <div v-else class="grid grid-main">
    <div class="stack">
      <section class="card">
        <div class="card-head"><div><h3>Kop surat</h3><p>Muncul di bagian atas setiap surat.</p></div></div>
        <div class="card-body form-grid">
          <FormField label="Isi kop (3 baris)" :error="errors.kop" full required hint="Baris pertama dicetak tebal. Contoh: nama RT/RW, nama perumahan, lalu desa/kecamatan/kabupaten.">
            <textarea v-model="form.kop" class="textarea" rows="3" style="font-family:inherit" />
          </FormField>
          <FormField label="Kota penandatanganan" :error="errors.kota" required hint="Tampil sebagai “Bekasi, 9 Oktober 2026”.">
            <input v-model="form.kota" class="input" required>
          </FormField>
        </div>
      </section>

      <section class="card">
        <div class="card-head"><div><h3>Penandatangan</h3><p>Nama dan jabatan yang tercetak di bawah tanda tangan.</p></div></div>
        <div class="card-body form-grid">
          <FormField label="Nama lengkap" :error="errors.penandatangan_nama" required><input v-model="form.penandatangan_nama" class="input" required></FormField>
          <FormField label="Jabatan" :error="errors.penandatangan_jabatan" required><input v-model="form.penandatangan_jabatan" class="input" placeholder="mis. Ketua RT 03" required></FormField>
        </div>
      </section>

      <section class="card">
        <div class="card-head"><div><h3>Tanda tangan & stempel</h3><p>Gambar PNG/JPG, maksimal 1 MB. PNG berlatar transparan hasilnya paling rapi.</p></div></div>
        <div class="card-body form-grid">
          <GambarSlot label="Tanda tangan" hint="Foto tanda tangan di kertas putih, atau gambar langsung." :src="preview.tanda_tangan" bisa-gambar @pilih="pilih('tanda_tangan', $event)" @hapus="buang('tanda_tangan')" @gambar="showPad = true" />
          <GambarSlot label="Stempel RT" hint="Foto stempel di kertas putih (bagian putih otomatis menyatu)." :src="preview.stempel" @pilih="pilih('stempel', $event)" @hapus="buang('stempel')" />
          <p v-if="errors.tanda_tangan || errors.stempel" class="err full" style="color:var(--bad);font-size:12.5px">{{ errors.tanda_tangan || errors.stempel }}</p>
        </div>
        <div class="card-body" style="padding-top:0">
          <div class="callout tone-info"><Info :size="18" /><span>Tanda tangan dan stempel <b>hanya dipasang pada surat berstatus “Diterbitkan”</b> — surat Draft tercetak dengan watermark. Setiap surat terbit juga memuat kode QR agar keasliannya bisa diperiksa siapa saja.</span></div>
        </div>
      </section>
    </div>

    <aside class="stack">
      <section class="card">
        <div class="card-head"><div><h3>Pratinjau</h3><p>Perkiraan tampilan pada surat.</p></div></div>
        <div class="card-body">
          <div class="kertas">
            <div class="kop"><b v-if="form.kop.split('\n')[0]">{{ form.kop.split('\n')[0] }}</b><span v-for="(l, i) in form.kop.split('\n').slice(1)" :key="i">{{ l }}</span></div>
            <div class="garis" /><div class="garis pendek" /><div class="garis" /><div class="garis pendek" />
            <div class="ttd">
              <small>{{ form.kota }}, {{ formatTanggal(hariIni()) }}</small>
              <small>{{ form.penandatangan_jabatan }}</small>
              <div class="sign">
                <img v-if="preview.stempel" :src="preview.stempel" class="stempel" alt="">
                <img v-if="preview.tanda_tangan" :src="preview.tanda_tangan" class="tt" alt="">
              </div>
              <b>{{ form.penandatangan_nama }}</b>
            </div>
          </div>
        </div>
      </section>

      <section class="card card-pad">
        <h3 class="row" style="font-size:14.5px;gap:8px"><ShieldCheck :size="18" /> Keamanan</h3>
        <ul class="aman">
          <li>Gambar disimpan privat di server, tidak bisa dibuka lewat tautan.</li>
          <li>Hanya Ketua RT dan Administrator yang dapat mengubah pengaturan ini.</li>
          <li>Hanya Ketua RT, Sekretaris, dan Administrator yang dapat menerbitkan surat.</li>
        </ul>
      </section>
    </aside>
  </div>

  <TandaTanganPad v-if="showPad" @close="showPad = false" @selesai="dariPad" />
</template>

<style scoped>
.kertas { background: #fff; color: #111; border: 1px solid var(--line); border-radius: 8px; padding: 16px 14px 14px; font-family: 'Times New Roman', serif; }
.kop { text-align: center; border-bottom: 3px double #111; padding-bottom: 6px; margin-bottom: 12px; font-size: 9px; line-height: 1.35; }
.kop b { display: block; font-size: 10.5px; }
.kop span { display: block; }
.garis { height: 5px; background: #e5e7eb; border-radius: 3px; margin: 7px 0; }
.garis.pendek { width: 70%; }
.ttd { width: 55%; margin: 14px 0 0 auto; text-align: center; display: flex; flex-direction: column; font-size: 9px; }
.sign { position: relative; height: 62px; }
.sign img { position: absolute; mix-blend-mode: multiply; }
.sign .stempel { height: 58px; left: -6px; top: 2px; opacity: .9; }
.sign .tt { height: 52px; left: 50%; transform: translateX(-35%); top: 6px; max-width: 80px; object-fit: contain; }
.ttd b { text-decoration: underline; font-size: 10px; }
.aman { margin: 10px 0 0; padding-left: 18px; color: var(--muted); font-size: 13px; display: flex; flex-direction: column; gap: 6px; }
</style>
