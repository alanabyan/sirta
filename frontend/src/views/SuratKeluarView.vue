<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Plus, Search, Pencil, Trash2, Send, Printer, Wand2 } from 'lucide-vue-next'
import api, { errorMessage } from '@/api'
import { useAuth } from '@/stores/auth'
import { useUi } from '@/stores/ui'
import { useList } from '@/composables/useList'
import { useSave } from '@/composables/useSave'
import { formatTanggal, hariIni, isiTemplate } from '@/utils'
import { cetakSurat } from '@/cetak'
import SearchSelect from '@/components/SearchSelect.vue'
import PageHeader from '@/components/PageHeader.vue'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import PaginationBar from '@/components/PaginationBar.vue'

const auth = useAuth()
const ui = useUi()
const route = useRoute()
const router = useRouter()
const list = useList('/surat-keluar', { filters: { status: '' } })
const { saving, errors, save } = useSave('/surat-keluar')
if (route.query.q) list.q.value = String(route.query.q) // datang dari permohonan: langsung menyaring surat terkait

const templates = ref([])
const wargaOpsi = ref([])

const kosong = () => ({
  id: null, nomor: '', tanggal: hariIni(), tujuan: '', perihal: '', isi: '', status: 'Draft',
  template_surat_id: '', warga_id: '', keperluan: '', pengajuan_id: '', pengajuan_kode: '',
})
const form = reactive(kosong())
const showForm = ref(false)

/** Isi surat otomatis dari template + data warga; tetap dapat disunting manual. */
// Isian yang belum terisi ({{nama}}, {{nik}}, …). {{nomor}}/{{tanggal}}/{{ttd}} diisi otomatis saat cetak.
const isianKosong = (isi) => [...new Set([...(isi || '').matchAll(/\{\{(?!nomor\}\}|tanggal\}\}|ttd\}\})(\w+)\}\}/g)].map((m) => `{{${m[1]}}}`))]
const kosongDiForm = computed(() => isianKosong(form.isi))

const nikCache = {}
async function nikWarga(id) {
  if (!id) return undefined
  if (!nikCache[id]) nikCache[id] = (await api.get(`/warga/${id}/nik`, { params: { untuk: 'surat' } })).data.data.nik
  return nikCache[id]
}

async function susun() {
  const t = templates.value.find((x) => x.id === Number(form.template_surat_id))
  if (!t) return
  const w = wargaOpsi.value.find((x) => x.id === Number(form.warga_id))
  form.isi = isiTemplate(t.isi, {
    nama: w?.nama,
    nik: await nikWarga(w?.id).catch(() => undefined),
    alamat: w?.keluarga?.alamat ? `${w.keluarga.alamat}, Perum Griya Kreasi Aqilla` : undefined,
    keperluan: form.keperluan,
  })
  if (!form.perihal) form.perihal = t.nama + (w ? ` — ${w.nama}` : '')
  if (!form.tujuan && w) form.tujuan = w.nama
}

function buka(s, prefill = {}) {
  Object.assign(form, kosong(), s ? { ...s, template_surat_id: s.template_surat_id ?? '', isi: s.isi ?? '', pengajuan_id: s.pengajuan_id ?? '', pengajuan_kode: s.pengajuan?.kode ?? '' } : {}, prefill)
  errors.value = {}
  showForm.value = true
  if (!s && form.template_surat_id) susun()
}

async function simpan() {
  const { id, warga_id, keperluan, pengajuan_kode, ...payload } = form
  payload.template_surat_id = payload.template_surat_id || null
  payload.pengajuan_id = payload.pengajuan_id || null
  if (!id) payload.nomor = ''
  const hasil = await save(id, payload)
  if (hasil) {
    showForm.value = false
    list.load()
  }
}

async function cetak(s) {
  if (!s.isi) return ui.error('Surat ini belum memiliki isi. Ubah surat dan pilih template terlebih dahulu.')
  const sisa = isianKosong(s.isi)
  if (sisa.length) ui.error(`Perhatian: surat ini masih memuat isian kosong (${sisa.join(', ')}). Ubah surat lalu pilih warga atau isi manual.`)
  const hasil = await cetakSurat(s)
  if (hasil.ok) return
  ui.error({
    popup: 'Pop-up diblokir browser. Izinkan pop-up untuk situs ini lalu coba lagi.',
    izin: 'Peran Anda tidak dapat mencetak surat bertanda tangan.',
  }[hasil.reason] || 'Gagal menyiapkan surat. Coba lagi.')
}

const hapus = (s) => list.remove(s, { title: `Hapus surat ${s.nomor}?`, message: 'Surat keluar ini akan dihapus permanen.' })

watch(() => [form.template_surat_id, form.warga_id], () => showForm.value && !form.id && susun())

onMounted(async () => {
  try {
    const [t, w] = await Promise.all([api.get('/template-surat', { params: { all: 1 } }), api.get('/warga', { params: { all: 1 } })])
    templates.value = t.data.data
    wargaOpsi.value = w.data.data
  } catch (e) {
    ui.error(errorMessage(e))
  }

  // Datang dari permohonan yang selesai → siapkan surat otomatis.
  if (route.query.pengajuan && auth.canWrite) {
    try {
      const p = (await api.get(`/pengajuan/${route.query.pengajuan}`)).data.data
      if (p.surat_keluar) {
        // Sudah pernah dibuatkan surat → arahkan ke surat itu, jangan dobel.
        ui.error(`Permohonan ${p.kode} sudah dibuatkan surat ${p.surat_keluar.nomor}.`)
        list.q.value = p.surat_keluar.nomor
        router.replace({ query: {} })
        return
      }
      const t = templates.value.find((x) => x.nama === p.layanan)
      buka(null, { pengajuan_id: p.id, pengajuan_kode: p.kode, template_surat_id: t?.id ?? '', warga_id: p.warga_id, keperluan: p.keperluan ?? '', tujuan: p.warga.nama, perihal: `${p.layanan} — ${p.warga.nama}`, status: auth.canDecide ? 'Diterbitkan' : 'Draft' })
      await susun()
    } catch (e) {
      ui.error(errorMessage(e))
    }
    router.replace({ query: {} })
  } else if (route.query.baru && auth.canWrite) {
    buka(null, { template_surat_id: Number(route.query.template) || '' })
  }
})
</script>

<template>
  <PageHeader title="Surat Keluar" description="Surat yang diterbitkan RT. Nomor surat dibuat otomatis, isi surat dapat disusun dari template.">
    <RouterLink class="btn btn-secondary" to="/template">Lihat template</RouterLink>
    <button v-if="auth.canWrite" class="btn btn-primary" @click="buka()"><Plus :size="18" /> Buat surat</button>
  </PageHeader>

  <div class="card">
    <div class="toolbar">
      <div class="search"><Search :size="18" /><input v-model="list.q.value" class="input" placeholder="Cari nomor, tujuan, atau perihal…" aria-label="Cari surat keluar"></div>
      <select v-model="list.filter.status" class="select" aria-label="Status"><option value="">Semua status</option><option>Draft</option><option>Diterbitkan</option></select>
    </div>

    <div v-if="list.loading.value && !list.items.value.length" class="loading"><div class="spinner" /></div>
    <div v-else-if="list.error.value" class="callout tone-bad" style="margin:16px">{{ list.error.value }}</div>
    <EmptyState v-else-if="!list.items.value.length" :icon="Send" title="Belum ada surat keluar" text="Buat surat pertama dari template yang tersedia." />
    <div v-else class="table-wrap">
      <table class="table">
        <thead><tr><th>Nomor surat</th><th>Tanggal</th><th>Tujuan</th><th>Perihal</th><th>Status</th><th /></tr></thead>
        <tbody>
          <tr v-for="s in list.items.value" :key="s.id">
            <td class="cell-title mono">{{ s.nomor }}</td>
            <td>{{ formatTanggal(s.tanggal, true) }}</td>
            <td>{{ s.tujuan }}</td>
            <td>{{ s.perihal }}<div v-if="s.pengajuan" class="cell-sub">Dari permohonan <b>{{ s.pengajuan.kode }}</b></div><div v-else-if="s.template" class="cell-sub">Template: {{ s.template.nama }}</div></td>
            <td><StatusBadge :status="s.status" /></td>
            <td class="actions">
              <button v-if="auth.canWrite" class="btn btn-soft btn-sm" @click="cetak(s)"><Printer :size="15" /> Cetak</button>
              <template v-if="auth.canWrite">
                <button v-if="auth.canDecide || s.status !== 'Diterbitkan'" class="btn btn-icon" title="Ubah" :aria-label="`Ubah ${s.nomor}`" @click="buka(s)"><Pencil :size="17" /></button>
                <button v-if="auth.canDecide" class="btn btn-icon" title="Hapus" :aria-label="`Hapus ${s.nomor}`" @click="hapus(s)"><Trash2 :size="17" /></button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar v-bind="list.meta" @change="list.goto" />
  </div>

  <BaseModal v-if="showForm" :title="form.id ? `Ubah surat ${form.nomor}` : 'Buat surat keluar'" subtitle="Pilih template (dan warga) agar isi surat terisi otomatis." size="lg" :saving="saving" @close="showForm = false" @save="simpan">
    <div class="form-grid">
      <div v-if="form.pengajuan_kode" class="full callout tone-info">
        <span>Surat ini dibuat untuk permohonan <b>{{ form.pengajuan_kode }}</b>. {{ errors.pengajuan_id }}</span>
      </div>
      <FormField v-if="!form.id" label="Template surat" :error="errors.template_surat_id">
        <select v-model="form.template_surat_id" class="select">
          <option value="">— Tanpa template —</option>
          <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.nama }}</option>
        </select>
      </FormField>
      <FormField v-if="!form.id" label="Untuk warga" hint="Opsional. Mengisi nama, NIK, dan alamat otomatis.">
        <SearchSelect v-model="form.warga_id" :options="wargaOpsi.map((w) => ({ value: w.id, label: w.nama, sub: w.keluarga?.alamat }))" placeholder="— Pilih warga —" search-placeholder="Ketik nama warga…" clearable />
      </FormField>
      <FormField v-if="!form.id" label="Keperluan" full hint="Dimasukkan ke bagian {{keperluan}} pada template.">
        <input v-model="form.keperluan" class="input" placeholder="mis. Pengurusan KTP-el" @change="susun">
      </FormField>
      <FormField label="Tanggal surat" :error="errors.tanggal" required><input v-model="form.tanggal" type="date" class="input" required></FormField>
      <FormField label="Status" :error="errors.status" :hint="auth.canDecide ? 'Diterbitkan = dibubuhi tanda tangan, stempel, dan kode QR saat dicetak.' : 'Hanya Ketua RT / Sekretaris yang dapat menerbitkan surat.'" required><select v-model="form.status" class="select" :disabled="!auth.canDecide"><option>Draft</option><option>Diterbitkan</option></select></FormField>
      <FormField label="Tujuan" :error="errors.tujuan" required><input v-model="form.tujuan" class="input" required></FormField>
      <FormField label="Perihal" :error="errors.perihal" required><input v-model="form.perihal" class="input" required></FormField>
      <div v-if="kosongDiForm.length" class="full callout tone-warn" role="alert">
        <span><b>Masih ada isian kosong:</b> {{ kosongDiForm.join(', ') }}. Pilih warga di atas atau ketik langsung pada isi surat. Surat dengan isian kosong tidak dapat diterbitkan.</span>
      </div>
      <FormField label="Isi surat" :error="errors.isi" full hint="{{nomor}} dan {{tanggal}} akan terisi otomatis saat dicetak.">
        <textarea v-model="form.isi" class="textarea" style="min-height:240px;font-family:ui-monospace,Consolas,monospace;font-size:13px" />
      </FormField>
    </div>
    <p v-if="!form.id" class="muted small" style="margin-top:10px"><Wand2 :size="14" style="vertical-align:-2px" /> Nomor surat dibuat otomatis oleh sistem saat disimpan.</p>
  </BaseModal>
</template>
