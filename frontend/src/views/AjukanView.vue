<script setup>
import { reactive, ref } from 'vue'
import { Home, ArrowLeft, CheckCircle2, Copy, Search, ShieldCheck, Paperclip, X, FileText, Image } from 'lucide-vue-next'
import api, { errorMessage, fieldErrors } from '@/api'
import FormField from '@/components/FormField.vue'
import { formatUkuran } from '@/utils'

const LAYANAN = [
  ['Surat Pengantar', 'Pengantar ke instansi lain (KTP-el, KK, dll.)'],
  ['Surat Domisili', 'Keterangan bertempat tinggal di RT ini'],
  ['Surat Keterangan', 'Keterangan umum dari RT'],
  ['Surat Keterangan Usaha', 'Keterangan usaha milik warga'],
  ['Surat Pengantar Nikah', 'Pengantar administrasi pernikahan'],
]

const form = reactive({ nik: '', tanggal_lahir: '', layanan: 'Surat Pengantar', keperluan: '' })
const loading = ref(false)
const error = ref('')
const errors = ref({})
const hasil = ref(null)
const tersalin = ref(false)

// Lampiran (opsional): foto KK/KTP atau PDF.
const MAKS_BERKAS = 3
const MAKS_BYTE = 2 * 1024 * 1024
const files = ref([])
const fileError = ref('')
const drag = ref(false)

function tambahFile(list) {
  fileError.value = ''
  for (const f of list) {
    if (files.value.length >= MAKS_BERKAS) { fileError.value = `Maksimal ${MAKS_BERKAS} berkas.`; break }
    if (!/\.(jpe?g|png|pdf)$/i.test(f.name)) { fileError.value = `“${f.name}” bukan foto JPG/PNG atau PDF.`; continue }
    if (f.size > MAKS_BYTE) { fileError.value = `“${f.name}” lebih dari 2 MB.`; continue }
    files.value.push(f)
  }
}
const hapusFile = (i) => files.value.splice(i, 1)
const lampiranError = () => Object.entries(errors.value).find(([k]) => k.startsWith('lampiran'))?.[1] || fileError.value

async function kirim() {
  loading.value = true
  error.value = ''
  errors.value = {}
  try {
    const fd = new FormData()
    Object.entries(form).forEach(([k, v]) => fd.append(k, v))
    files.value.forEach((f) => fd.append('lampiran[]', f))
    hasil.value = (await api.post('/ajukan', fd)).data.data
  } catch (e) {
    errors.value = fieldErrors(e)
    error.value = Object.keys(errors.value).length ? '' : errorMessage(e)
  } finally {
    loading.value = false
  }
}

function ajukanLagi() {
  Object.assign(form, { nik: '', tanggal_lahir: '', keperluan: '' })
  files.value = []
  hasil.value = null
}

async function salin() {
  try {
    await navigator.clipboard.writeText(hasil.value.kode)
    tersalin.value = true
    setTimeout(() => (tersalin.value = false), 2000)
  } catch {
    /* abaikan — kode tetap tampil besar di layar */
  }
}
</script>

<template>
  <div class="pub">
    <header>
      <RouterLink to="/masuk" class="brand"><span class="logo"><Home :size="18" /></span><b>SIRTA</b><span class="muted small">RT 03 / RW 20</span></RouterLink>
      <RouterLink to="/lacak" class="btn btn-secondary btn-sm"><Search :size="15" /> Lacak permohonan</RouterLink>
    </header>

    <main>
      <!-- Berhasil -->
      <div v-if="hasil" class="card card-pad done">
        <CheckCircle2 :size="44" />
        <h1>Permohonan terkirim!</h1>
        <p class="muted">Pengurus RT akan memeriksa permohonan <b>{{ hasil.layanan }}</b> Anda. <b>Simpan kode berikut</b> untuk memantau prosesnya.</p>
        <div class="kode">{{ hasil.kode }}</div>
        <button class="btn btn-secondary btn-sm" @click="salin"><Copy :size="15" /> {{ tersalin ? 'Tersalin ✓' : 'Salin kode' }}</button>
        <div class="callout tone-info" style="text-align:left;margin-top:18px">
          Untuk melacak, masukkan kode ini dan <b>4 digit terakhir NIK Anda</b> (••••{{ hasil.nik4 }}) di halaman Lacak.
        </div>
        <div class="row" style="justify-content:center;margin-top:18px">
          <RouterLink class="btn btn-primary" :to="`/lacak/${hasil.kode}`">Lacak permohonan ini</RouterLink>
          <button class="btn btn-secondary" @click="ajukanLagi">Ajukan lagi</button>
        </div>
      </div>

      <!-- Formulir -->
      <template v-else>
        <h1>Ajukan surat ke RT</h1>
        <p class="muted">Isi data berikut. Tidak perlu datang ke rumah pengurus — Anda tinggal memantau prosesnya di sini.</p>

        <form class="card card-pad stack" style="margin-top:22px" @submit.prevent="kirim">
          <div v-if="error" class="callout tone-bad" role="alert">{{ error }}</div>

          <div class="form-grid">
            <FormField label="NIK" :error="errors.nik" hint="16 digit sesuai KTP" required>
              <input v-model="form.nik" class="input mono" inputmode="numeric" maxlength="16" placeholder="3275…" required autocomplete="off" @input="form.nik = form.nik.replace(/\D/g, '')">
            </FormField>
            <FormField label="Tanggal lahir" :error="errors.tanggal_lahir" hint="Untuk memastikan ini benar Anda" required>
              <input v-model="form.tanggal_lahir" type="date" class="input" required>
            </FormField>
          </div>

          <FormField label="Jenis surat" :error="errors.layanan" required>
            <div class="opts">
              <label v-for="l in LAYANAN" :key="l[0]" class="opt" :class="{ on: form.layanan === l[0] }">
                <input v-model="form.layanan" type="radio" :value="l[0]" class="sr-only">
                <b>{{ l[0] }}</b><small>{{ l[1] }}</small>
              </label>
            </div>
          </FormField>

          <FormField label="Keperluan" :error="errors.keperluan" hint="Jelaskan untuk apa surat ini dibutuhkan." required>
            <textarea v-model="form.keperluan" class="textarea" placeholder="mis. Persyaratan pendaftaran sekolah anak" required />
          </FormField>

          <FormField label="Lampiran (opsional)" :error="lampiranError()" hint="Foto KK/KTP atau dokumen pendukung. JPG, PNG, atau PDF · maks. 3 berkas · tiap berkas maks. 2 MB.">
            <label class="drop" :class="{ drag }" @dragover.prevent="drag = true" @dragleave="drag = false" @drop.prevent="drag = false; tambahFile($event.dataTransfer.files)">
              <Paperclip :size="20" />
              <span><b>Pilih berkas</b> atau seret ke sini</span>
              <input type="file" class="sr-only" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" multiple @change="tambahFile($event.target.files); $event.target.value = ''">
            </label>
            <ul v-if="files.length" class="files">
              <li v-for="(f, i) in files" :key="f.name + i">
                <component :is="/pdf$/i.test(f.name) ? FileText : Image" :size="18" />
                <span class="grow truncate">{{ f.name }}</span>
                <small class="muted">{{ formatUkuran(f.size) }}</small>
                <button type="button" class="btn btn-icon" :aria-label="`Hapus ${f.name}`" @click="hapusFile(i)"><X :size="16" /></button>
              </li>
            </ul>
          </FormField>

          <p class="muted small row" style="align-items:flex-start"><ShieldCheck :size="16" style="flex:none;margin-top:2px" /> NIK dan tanggal lahir hanya dipakai untuk mencocokkan data Anda dengan data warga RT. Hanya warga yang terdaftar yang dapat mengajukan.</p>

          <button class="btn btn-primary btn-block" :disabled="loading || form.nik.length !== 16 || !form.tanggal_lahir || !form.keperluan.trim()">{{ loading ? 'Mengirim…' : 'Kirim permohonan' }}</button>
        </form>
        <RouterLink to="/masuk" class="back"><ArrowLeft :size="15" /> Kembali ke halaman masuk pengurus</RouterLink>
      </template>
    </main>
  </div>
</template>

<style scoped>
.pub { min-height: 100vh; }
header { display: flex; justify-content: space-between; align-items: center; padding: 14px 28px; background: var(--surface); border-bottom: 1px solid var(--line); }
.brand { display: flex; align-items: center; gap: 10px; color: var(--text); text-decoration: none !important; }
.logo { width: 34px; height: 34px; border-radius: 10px; background: var(--primary); color: #fff; display: grid; place-items: center; }
main { max-width: 640px; margin: 0 auto; padding: 40px 20px 60px; }
h1 { font-size: 28px; margin-bottom: 6px; letter-spacing: -.01em; }
.opts { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.opt { border: 1.5px solid var(--line); border-radius: 12px; padding: 11px 13px; cursor: pointer; display: flex; flex-direction: column; background: var(--surface); }
.opt:hover { border-color: var(--faint); }
.opt.on { border-color: var(--primary); background: var(--primary-soft); }
.opt:has(:focus-visible) { outline: 2px solid var(--primary); outline-offset: 2px; }
.opt b { font-size: 13.5px; }
.opt small { color: var(--muted); font-size: 12px; }
.drop { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 16px; border: 2px dashed var(--line); border-radius: 12px; cursor: pointer; color: var(--muted); }
.drop:hover, .drop.drag { border-color: var(--primary); background: var(--primary-soft); color: var(--primary); }
.drop b { color: var(--text); }
.files { list-style: none; margin: 8px 0 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.files li { display: flex; align-items: center; gap: 10px; padding: 6px 6px 6px 12px; background: var(--surface-2); border-radius: 10px; }
.back { display: inline-flex; align-items: center; gap: 6px; margin-top: 18px; font-size: 13px; }
.done { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 36px 28px; }
.done > svg { color: var(--ok); }
.kode { font-size: 30px; font-weight: 800; letter-spacing: .04em; background: var(--primary-soft); color: var(--primary-strong); padding: 10px 22px; border-radius: 14px; margin: 6px 0; }
@media (max-width: 560px) { .opts { grid-template-columns: 1fr; } }
@media (max-width: 480px) { header { padding: 12px 16px; } .brand .muted { display: none; } }
</style>
