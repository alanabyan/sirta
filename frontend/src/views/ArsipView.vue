<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { Plus, Search, Trash2, Archive, FileText, FileSpreadsheet, FileArchive, File, Download, UploadCloud, Pencil } from 'lucide-vue-next'
import api, { errorMessage } from '@/api'
import { useUi } from '@/stores/ui'
import { useAuth } from '@/stores/auth'
import { useList } from '@/composables/useList'
import { useSave } from '@/composables/useSave'
import { formatTanggal, formatUkuran } from '@/utils'
import PageHeader from '@/components/PageHeader.vue'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'
import EmptyState from '@/components/EmptyState.vue'
import PaginationBar from '@/components/PaginationBar.vue'

const KATEGORI = ['Surat', 'Kependudukan', 'Keuangan', 'Kegiatan']
const auth = useAuth()
const ui = useUi()
const route = useRoute()
const list = useList('/arsip', { filters: { kategori: '' } })
const { saving, errors, save } = useSave('/arsip')
const ringkasan = ref({ total: 0, ukuran: 0, per_kategori: {} })
const loadRingkasan = async () => (ringkasan.value = (await api.get('/arsip/ringkasan')).data.data)

const form = reactive({ id: null, nama: '', kategori: 'Surat', file: null })
const showForm = ref(false)
const drag = ref(false)

function buka(a) {
  Object.assign(form, { id: a?.id ?? null, nama: a?.nama ?? '', kategori: a?.kategori ?? 'Surat', file: null })
  errors.value = {}
  showForm.value = true
}
function pilihFile(f) {
  if (!f) return
  form.file = f
  if (!form.nama) form.nama = f.name
}
async function simpan() {
  let hasil
  if (form.id) {
    hasil = await save(form.id, { nama: form.nama, kategori: form.kategori })
  } else {
    const fd = new FormData()
    fd.append('kategori', form.kategori)
    if (form.nama) fd.append('nama', form.nama)
    if (form.file) fd.append('file', form.file)
    hasil = await save(null, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
  }
  if (hasil) {
    showForm.value = false
    list.load()
    loadRingkasan()
  }
}
async function hapus(a) {
  if (await list.remove(a, { title: `Hapus “${a.nama}”?`, message: 'Dokumen akan dihapus permanen dari arsip.' })) loadRingkasan()
}

/** Berkas disimpan privat; diambil lewat API ber-login lalu diunduh dari memori browser. */
async function unduh(a) {
  try {
    const { data } = await api.get(`/arsip/${a.id}/unduh`, { responseType: 'blob' })
    const url = URL.createObjectURL(data)
    const el = Object.assign(document.createElement('a'), { href: url, download: a.nama })
    document.body.appendChild(el)
    el.click()
    el.remove()
    setTimeout(() => URL.revokeObjectURL(url), 10000)
  } catch (e) {
    ui.error(e.response?.status === 404 ? 'Berkas tidak ditemukan di server.' : errorMessage(e))
  }
}

function ikon(nama) {
  const e = nama.split('.').pop().toLowerCase()
  if (['xls', 'xlsx', 'csv'].includes(e)) return FileSpreadsheet
  if (['zip', 'rar', '7z'].includes(e)) return FileArchive
  if (['pdf', 'doc', 'docx', 'txt'].includes(e)) return FileText
  return File
}

onMounted(() => {
  loadRingkasan()
  if (route.query.baru && auth.canWrite) buka()
})
</script>

<template>
  <PageHeader title="Arsip Digital" description="Simpan dokumen RT di satu tempat, dikelompokkan per kategori agar mudah dicari.">
    <button v-if="auth.canWrite" class="btn btn-primary" @click="buka()"><Plus :size="18" /> Unggah dokumen</button>
  </PageHeader>

  <div class="grid grid-4" style="margin-bottom:16px">
    <div class="card card-pad"><div class="muted small">Total dokumen</div><b class="big">{{ ringkasan.total }}</b></div>
    <div class="card card-pad"><div class="muted small">Ruang terpakai</div><b class="big">{{ formatUkuran(ringkasan.ukuran) }}</b></div>
    <div class="card card-pad" style="grid-column: span 2">
      <div class="muted small" style="margin-bottom:8px">Per kategori</div>
      <div class="row row-wrap">
        <button v-for="k in KATEGORI" :key="k" class="chip" :class="{ on: list.filter.kategori === k }" @click="list.filter.kategori = list.filter.kategori === k ? '' : k">
          {{ k }} <b>{{ ringkasan.per_kategori[k] || 0 }}</b>
        </button>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="toolbar">
      <div class="search"><Search :size="18" /><input v-model="list.q.value" class="input" placeholder="Cari nama dokumen…" aria-label="Cari dokumen"></div>
      <select v-model="list.filter.kategori" class="select" aria-label="Kategori"><option value="">Semua kategori</option><option v-for="k in KATEGORI" :key="k">{{ k }}</option></select>
    </div>

    <div v-if="list.loading.value && !list.items.value.length" class="loading"><div class="spinner" /></div>
    <div v-else-if="list.error.value" class="callout tone-bad" style="margin:16px">{{ list.error.value }}</div>
    <EmptyState v-else-if="!list.items.value.length" :icon="Archive" title="Arsip masih kosong" text="Unggah dokumen pertama Anda." />
    <div v-else class="table-wrap">
      <table class="table">
        <thead><tr><th>Dokumen</th><th>Kategori</th><th>Diunggah</th><th>Ukuran</th><th /></tr></thead>
        <tbody>
          <tr v-for="a in list.items.value" :key="a.id">
            <td>
              <div class="row">
                <span class="file-ic"><component :is="ikon(a.nama)" :size="20" /></span>
                <div><div class="cell-title">{{ a.nama }}</div><div class="cell-sub">oleh {{ a.user?.name || '—' }}</div></div>
              </div>
            </td>
            <td><span class="badge plain tone-primary">{{ a.kategori }}</span></td>
            <td>{{ formatTanggal(a.created_at, true) }}</td>
            <td>{{ formatUkuran(a.ukuran) }}</td>
            <td class="actions">
              <button v-if="a.ada_berkas" class="btn btn-soft btn-sm" @click="unduh(a)"><Download :size="15" /> Unduh</button>
              <span v-else class="faint small">Tanpa berkas</span>
              <template v-if="auth.canWrite">
                <button class="btn btn-icon" title="Ubah nama/kategori" @click="buka(a)"><Pencil :size="17" /></button>
                <button v-if="auth.canDecide" class="btn btn-icon" title="Hapus" @click="hapus(a)"><Trash2 :size="17" /></button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar v-bind="list.meta" @change="list.goto" />
  </div>

  <BaseModal v-if="showForm" :title="form.id ? 'Ubah dokumen' : 'Unggah dokumen'" size="sm" :saving="saving" :save-text="form.id ? 'Simpan' : 'Unggah'" @close="showForm = false" @save="simpan">
    <div class="stack">
      <label v-if="!form.id" class="drop" :class="{ drag }" @dragover.prevent="drag = true" @dragleave="drag = false" @drop.prevent="drag = false; pilihFile($event.dataTransfer.files[0])">
        <UploadCloud :size="28" />
        <b>{{ form.file ? form.file.name : 'Pilih atau seret berkas ke sini' }}</b>
        <small>{{ form.file ? formatUkuran(form.file.size) : 'PDF, Word, Excel, gambar, ZIP · maks. 10 MB' }}</small>
        <input type="file" class="sr-only" @change="pilihFile($event.target.files[0])">
      </label>
      <FormField label="Nama dokumen" :error="errors.nama || errors.file" :required="!!form.id"><input v-model="form.nama" class="input" placeholder="Otomatis dari nama berkas"></FormField>
      <FormField label="Kategori" :error="errors.kategori" required><select v-model="form.kategori" class="select"><option v-for="k in KATEGORI" :key="k">{{ k }}</option></select></FormField>
    </div>
  </BaseModal>
</template>

<style scoped>
.big { font-size: 28px; letter-spacing: -.02em; display: block; margin-top: 2px; }
.chip { border: 1px solid var(--line); background: var(--surface); border-radius: 99px; padding: 5px 12px; font-size: 13px; font-weight: 600; }
.chip b { color: var(--primary); margin-left: 4px; }
.chip.on, .chip:hover { border-color: var(--primary); background: var(--primary-soft); }
.file-ic { width: 38px; height: 38px; border-radius: 11px; background: var(--info-soft); color: var(--info); display: grid; place-items: center; flex: none; }
.drop { display: flex; flex-direction: column; align-items: center; gap: 4px; text-align: center; padding: 26px 16px; border: 2px dashed var(--line); border-radius: 14px; cursor: pointer; color: var(--muted); }
.drop:hover, .drop.drag { border-color: var(--primary); background: var(--primary-soft); color: var(--primary); }
.drop b { color: var(--text); word-break: break-all; }
</style>
