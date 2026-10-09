<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { Plus, Search, Baby, LogIn, LogOut, HeartCrack, Undo2, ArrowLeftRight, ChevronLeft } from 'lucide-vue-next'
import api, { errorMessage } from '@/api'
import { useUi } from '@/stores/ui'
import { useList } from '@/composables/useList'
import { useSave } from '@/composables/useSave'
import { formatTanggal, hariIni } from '@/utils'
import PageHeader from '@/components/PageHeader.vue'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'
import SearchSelect from '@/components/SearchSelect.vue'
import EmptyState from '@/components/EmptyState.vue'
import PaginationBar from '@/components/PaginationBar.vue'

const ui = useUi()
const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
const bulanIni = () => new Date().toISOString().slice(0, 7)
const namaBulan = (ym) => (ym ? `${BULAN[Number(ym.slice(5)) - 1]} ${ym.slice(0, 4)}` : 'Semua waktu')

const JENIS = {
  lahir: { label: 'Lahir', ikon: Baby, tone: 'ok', judul: 'Kelahiran', info: 'Mencatat bayi yang lahir di RT ini dan menambahkannya sebagai warga.' },
  masuk: { label: 'Pindah masuk', ikon: LogIn, tone: 'info', judul: 'Pindah masuk', info: 'Warga baru yang pindah dan menetap di RT ini.' },
  keluar: { label: 'Pindah keluar', ikon: LogOut, tone: 'warn', judul: 'Pindah keluar', info: 'Warga yang pindah ke luar RT. Statusnya menjadi “Pindah”.' },
  meninggal: { label: 'Meninggal', ikon: HeartCrack, tone: 'neutral', judul: 'Meninggal dunia', info: 'Mencatat warga yang meninggal. Statusnya menjadi “Meninggal”.' },
}

const list = useList('/mutasi', { filters: { bulan: bulanIni(), jenis: '' } })
const { saving, errors, save } = useSave('/mutasi')

// ---------- ringkasan bulan terpilih ----------
const rekap = ref(null)
async function muatRekap() {
  rekap.value = list.filter.bulan ? (await api.get('/mutasi/ringkasan', { params: { bulan: list.filter.bulan } })).data.data : null
}
watch(() => list.filter.bulan, muatRekap)

// ---------- pilihan warga & KK ----------
const wargaAktif = ref([])
const keluargaOpsi = ref([])
async function muatOpsi() {
  const [w, k] = await Promise.all([api.get('/warga', { params: { all: 1, status: 'Aktif' } }), api.get('/keluarga', { params: { all: 1 } })])
  wargaAktif.value = w.data.data
  keluargaOpsi.value = k.data.data
}
const opsiWarga = computed(() => wargaAktif.value.map((w) => ({ value: w.id, label: w.nama, sub: [w.nik_samar, w.keluarga?.alamat].filter(Boolean).join(' · ') })))
const opsiKeluarga = computed(() => keluargaOpsi.value.map((k) => ({ value: k.id, label: k.kepala_keluarga, sub: k.alamat })))

// ---------- formulir catat mutasi ----------
const kosong = () => ({
  jenis: '', tanggal: hariIni(), keterangan: '', warga_id: '',
  nama: '', nik: '', jenis_kelamin: 'Laki-laki', tanggal_lahir: '', pekerjaan: '', keluarga_id: '', hubungan_keluarga: '',
})
const form = reactive(kosong())
const showForm = ref(false)

function buka() {
  Object.assign(form, kosong())
  errors.value = {}
  showForm.value = true
}
function pilihJenis(j) {
  form.jenis = j
  form.hubungan_keluarga = j === 'lahir' ? 'Anak' : ''
  errors.value = {}
}
const labelKet = computed(() => ({ lahir: 'Keterangan (mis. nama orang tua)', masuk: 'Asal daerah', keluar: 'Tujuan pindah', meninggal: 'Catatan (opsional)' }[form.jenis]))
const baru = computed(() => ['lahir', 'masuk'].includes(form.jenis))

async function simpan() {
  const payload = { jenis: form.jenis, tanggal: form.tanggal, keterangan: form.keterangan || null }
  if (baru.value) {
    payload.warga = {
      nama: form.nama, nik: form.nik || null, jenis_kelamin: form.jenis_kelamin,
      tanggal_lahir: form.jenis === 'lahir' ? form.tanggal : form.tanggal_lahir,
      pekerjaan: form.pekerjaan || null, keluarga_id: form.keluarga_id || null, hubungan_keluarga: form.hubungan_keluarga || null,
    }
  } else {
    if (!form.warga_id) { errors.value = { warga_id: 'Pilih warga yang bersangkutan.' }; return }
    const w = wargaAktif.value.find((x) => x.id === form.warga_id)
    const ok = await ui.ask({
      title: `Catat ${JENIS[form.jenis].label.toLowerCase()}: ${w?.nama}?`,
      message: `Status ${w?.nama} akan menjadi “${form.jenis === 'meninggal' ? 'Meninggal' : 'Pindah'}” dan tidak lagi dihitung sebagai warga aktif. Bisa dibatalkan bila salah catat.`,
      confirmText: 'Ya, catat',
    })
    if (!ok) return
    payload.warga_id = form.warga_id
  }
  if (await save(null, payload)) {
    showForm.value = false
    list.load(); muatRekap(); muatOpsi()
  }
}

// ---------- pembatalan ----------
const batalItem = ref(null)
const alasan = ref('')
const membatalkan = ref(false)
function mulaiBatal(m) {
  batalItem.value = m
  alasan.value = ''
}
async function batalkan() {
  membatalkan.value = true
  try {
    const { data } = await api.post(`/mutasi/${batalItem.value.id}/batal`, { alasan: alasan.value })
    ui.success(data.message)
    batalItem.value = null
    list.load(); muatRekap(); muatOpsi()
  } catch (e) {
    ui.error(e.response?.data?.errors?.alasan?.[0] || errorMessage(e))
  } finally {
    membatalkan.value = false
  }
}

onMounted(() => { muatRekap(); muatOpsi() })
</script>

<template>
  <PageHeader title="Mutasi Warga" description="Catat kelahiran, pindah masuk, pindah keluar, dan warga yang meninggal. Rekapnya otomatis masuk ke laporan bulanan.">
    <button class="btn btn-primary" @click="buka"><Plus :size="18" /> Catat mutasi</button>
  </PageHeader>

  <!-- Saldo penduduk -->
  <div v-if="rekap" class="saldo card">
    <div class="sal"><small>Warga aktif awal bulan</small><b>{{ rekap.saldo_awal }}</b></div>
    <div class="op plus"><span>+</span><div><small>Lahir</small><b>{{ rekap.lahir }}</b></div></div>
    <div class="op plus"><span>+</span><div><small>Pindah masuk</small><b>{{ rekap.masuk }}</b></div></div>
    <div class="op minus"><span>−</span><div><small>Pindah keluar</small><b>{{ rekap.keluar }}</b></div></div>
    <div class="op minus"><span>−</span><div><small>Meninggal</small><b>{{ rekap.meninggal }}</b></div></div>
    <div class="sal akhir"><small>Warga aktif akhir bulan</small><b>{{ rekap.saldo_akhir }}</b></div>
  </div>
  <p v-if="rekap" class="muted small note">Saldo dihitung dari mutasi yang tercatat di sini, untuk {{ namaBulan(list.filter.bulan) }}.</p>

  <div class="card">
    <div class="toolbar">
      <div class="search"><Search :size="18" /><input v-model="list.q.value" class="input" placeholder="Cari nama warga…" aria-label="Cari mutasi"></div>
      <input v-model="list.filter.bulan" type="month" class="input bulan" aria-label="Bulan" :max="bulanIni()">
      <select v-model="list.filter.jenis" class="select" aria-label="Jenis mutasi">
        <option value="">Semua jenis</option>
        <option v-for="(j, k) in JENIS" :key="k" :value="k">{{ j.label }}</option>
      </select>
    </div>

    <div v-if="list.loading.value && !list.items.value.length" class="loading"><div class="spinner" /></div>
    <div v-else-if="list.error.value" class="callout tone-bad" style="margin:16px">{{ list.error.value }}</div>
    <EmptyState v-else-if="!list.items.value.length" :icon="ArrowLeftRight" title="Belum ada mutasi" :text="`Tidak ada mutasi tercatat untuk ${namaBulan(list.filter.bulan)}.`" />
    <div v-else class="table-wrap">
      <table class="table">
        <thead><tr><th>Warga</th><th>Jenis</th><th>Tanggal</th><th>Keterangan</th><th>Dicatat oleh</th><th /></tr></thead>
        <tbody>
          <tr v-for="m in list.items.value" :key="m.id" :class="{ batal: m.dibatalkan_at }">
            <td class="cell-title">{{ m.nama_warga }}</td>
            <td><span class="badge" :class="`tone-${JENIS[m.jenis]?.tone}`"><component :is="JENIS[m.jenis]?.ikon" :size="13" /> {{ m.jenis_label }}</span></td>
            <td>{{ formatTanggal(m.tanggal, true) }}</td>
            <td>{{ m.keterangan || '—' }}<div v-if="m.dibatalkan_at" class="cell-sub bad">Dibatalkan: {{ m.alasan_batal }}</div></td>
            <td>{{ m.user?.name || '—' }}</td>
            <td class="actions">
              <span v-if="m.dibatalkan_at" class="badge plain tone-neutral">Dibatalkan</span>
              <button v-else class="btn btn-secondary btn-sm" @click="mulaiBatal(m)"><Undo2 :size="14" /> Batalkan</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar v-bind="list.meta" @change="list.goto" />
  </div>

  <!-- Catat mutasi -->
  <BaseModal v-if="showForm" :title="form.jenis ? `Catat ${JENIS[form.jenis].judul.toLowerCase()}` : 'Catat mutasi'" :subtitle="form.jenis ? JENIS[form.jenis].info : 'Pilih jenis mutasi yang terjadi.'" size="lg" :saving="saving" :hide-footer="!form.jenis" @close="showForm = false" @save="simpan">
    <div v-if="!form.jenis" class="pilih">
      <button v-for="(j, k) in JENIS" :key="k" type="button" @click="pilihJenis(k)">
        <span class="ic" :class="`tone-${j.tone}`"><component :is="j.ikon" :size="24" /></span>
        <b>{{ j.judul }}</b>
        <small>{{ j.info }}</small>
      </button>
    </div>

    <div v-else class="form-grid">
      <button type="button" class="btn btn-secondary btn-sm full ganti" @click="form.jenis = ''"><ChevronLeft :size="15" /> Ganti jenis mutasi</button>

      <FormField label="Tanggal kejadian" :error="errors.tanggal" required>
        <input v-model="form.tanggal" type="date" class="input" :max="hariIni()" required>
      </FormField>
      <FormField :label="labelKet" :error="errors.keterangan" :required="form.jenis === 'keluar'"><input v-model="form.keterangan" class="input" :required="form.jenis === 'keluar'"></FormField>

      <!-- warga yang sudah ada -->
      <FormField v-if="!baru" label="Warga yang bersangkutan" :error="errors.warga_id" full required hint="Hanya warga berstatus Aktif yang dapat dipilih.">
        <SearchSelect v-model="form.warga_id" :options="opsiWarga" placeholder="— Pilih warga —" search-placeholder="Ketik nama warga…" :invalid="!!errors.warga_id" />
      </FormField>

      <!-- warga baru -->
      <template v-else>
        <FormField label="Nama lengkap" :error="errors['warga.nama']" required><input v-model="form.nama" class="input" required></FormField>
        <FormField label="Jenis kelamin" :error="errors['warga.jenis_kelamin']" required>
          <select v-model="form.jenis_kelamin" class="select"><option>Laki-laki</option><option>Perempuan</option></select>
        </FormField>
        <FormField v-if="form.jenis === 'masuk'" label="Tanggal lahir" :error="errors['warga.tanggal_lahir']" required><input v-model="form.tanggal_lahir" type="date" class="input" :max="hariIni()" required></FormField>
        <FormField label="NIK" :error="errors['warga.nik']" :required="form.jenis === 'masuk'" :hint="form.jenis === 'lahir' ? 'Boleh dikosongkan, diisi menyusul setelah KK terbit.' : '16 digit sesuai KTP'">
          <input v-model="form.nik" class="input mono" inputmode="numeric" maxlength="16" :required="form.jenis === 'masuk'" @input="form.nik = form.nik.replace(/\D/g, '')">
        </FormField>
        <FormField v-if="form.jenis === 'masuk'" label="Pekerjaan" :error="errors['warga.pekerjaan']"><input v-model="form.pekerjaan" class="input"></FormField>
        <FormField label="Masuk keluarga (KK)" :error="errors['warga.keluarga_id']" hint="Pilih bila sudah tergabung dalam sebuah KK di RT ini.">
          <SearchSelect v-model="form.keluarga_id" :options="opsiKeluarga" placeholder="— Belum terhubung —" search-placeholder="Ketik nama kepala keluarga…" clearable />
        </FormField>
        <FormField label="Hubungan dalam keluarga" :error="errors['warga.hubungan_keluarga']">
          <select v-model="form.hubungan_keluarga" class="select"><option value="">—</option><option>Kepala Keluarga</option><option>Istri</option><option>Anak</option><option>Famili Lain</option></select>
        </FormField>
      </template>
    </div>

    <template #footer>
      <button type="button" class="btn btn-secondary" @click="showForm = false">Batal</button>
      <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Catat mutasi' }}</button>
    </template>
  </BaseModal>

  <!-- Batalkan -->
  <BaseModal v-if="batalItem" title="Batalkan mutasi?" :subtitle="`${batalItem.jenis_label} · ${batalItem.nama_warga}`" size="sm" @close="batalItem = null" @save="batalkan">
    <div class="stack">
      <div class="callout tone-warn">
        <span v-if="['keluar', 'meninggal'].includes(batalItem.jenis)">Status {{ batalItem.nama_warga }} dikembalikan menjadi <b>Aktif</b>.</span>
        <span v-else>Data warga <b>{{ batalItem.nama_warga }}</b> yang dibuat oleh mutasi ini akan <b>dihapus</b>. Hanya bisa bila belum punya permohonan.</span>
      </div>
      <FormField label="Alasan pembatalan" required hint="Catatan pembatalan tetap tersimpan."><textarea v-model="alasan" class="textarea" style="min-height:70px" placeholder="mis. Salah memilih warga" required /></FormField>
    </div>
    <template #footer>
      <button type="button" class="btn btn-secondary" @click="batalItem = null">Kembali</button>
      <button type="submit" class="btn btn-solid-danger" :disabled="membatalkan || !alasan.trim()">{{ membatalkan ? 'Memproses…' : 'Batalkan mutasi' }}</button>
    </template>
  </BaseModal>
</template>

<style scoped>
.saldo { display: flex; align-items: stretch; gap: 8px; padding: 14px 16px; flex-wrap: wrap; margin-bottom: 8px; }
.saldo small { display: block; font-size: 12px; color: var(--muted); font-weight: 600; }
.saldo b { font-size: 24px; letter-spacing: -.02em; line-height: 1.15; }
.sal { background: var(--surface-2); border-radius: 12px; padding: 10px 16px; min-width: 150px; }
.sal.akhir { background: var(--primary-soft); }
.sal.akhir b { color: var(--primary-strong); }
.op { display: flex; align-items: center; gap: 10px; padding: 10px 6px; flex: 1; min-width: 110px; }
.op > span { width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center; font-weight: 800; flex: none; }
.op.plus > span { background: var(--ok-soft); color: var(--ok); }
.op.minus > span { background: var(--bad-soft); color: var(--bad); }
.note { margin: 0 4px 14px; }
.bulan { width: auto; }
tr.batal td { opacity: .65; }
tr.batal .cell-title { text-decoration: line-through; }
.cell-sub.bad { color: var(--bad); }
.pilih { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.pilih button { text-align: left; background: var(--surface); border: 1.5px solid var(--line); border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 4px; transition: border-color .12s, transform .1s; }
.pilih button:hover { border-color: var(--primary); transform: translateY(-2px); }
.pilih .ic { width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center; margin-bottom: 6px; }
.pilih b { font-size: 15px; }
.pilih small { color: var(--muted); font-size: 12.5px; }
.ganti { justify-self: start; }
@media (max-width: 640px) { .pilih { grid-template-columns: 1fr; } .sal { flex: 1; } }
</style>
