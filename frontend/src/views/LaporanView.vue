<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { FileSpreadsheet, Printer, CalendarRange, Users, UsersRound, ShieldAlert } from 'lucide-vue-next'
import api, { errorMessage } from '@/api'
import { useUi } from '@/stores/ui'
import { cetakLaporan, unduhExcel } from '@/ekspor'
import { formatTanggal } from '@/utils'
import PageHeader from '@/components/PageHeader.vue'

const ui = useUi()
const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
const namaBulan = (ym) => `${BULAN[Number(ym.slice(5)) - 1]} ${ym.slice(0, 4)}`

const bulan = ref(new Date().toISOString().slice(0, 7))
const data = ref(null)
const loading = ref(true)
const sibuk = ref('') // kunci tombol yang sedang berjalan
const nikLengkap = ref(false)

async function muat() {
  loading.value = true
  try {
    data.value = (await api.get('/laporan/bulanan', { params: { bulan: bulan.value } })).data.data
  } catch (e) {
    ui.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}
watch(bulan, (b) => b && muat())
onMounted(muat)

// ---------- penyusun laporan (struktur dipakai Excel & PDF) ----------
const R = computed(() => data.value?.ringkasan)

function laporanBulanan() {
  const d = data.value
  const r = d.ringkasan
  return {
    berkas: `laporan-bulanan-${d.bulan}`,
    judul: 'Laporan Bulanan RT 03 / RW 20',
    periode: `Periode ${namaBulan(d.bulan)}`,
    ringkasan: [
      ['Warga aktif', r.warga_aktif], ['Keluarga (KK)', r.keluarga],
      ['Permohonan masuk bulan ini', r.permohonan], ['— Selesai', r.selesai], ['— Ditolak', r.ditolak], ['— Belum selesai', r.belum_selesai],
      ['Diajukan mandiri oleh warga', r.mandiri], ['Rata-rata waktu selesai (hari)', r.rata_hari ?? '—'],
      ['Warga aktif awal bulan', d.mutasi_rekap.saldo_awal], ['+ Lahir', d.mutasi_rekap.lahir], ['+ Pindah masuk', d.mutasi_rekap.masuk], ['− Pindah keluar', d.mutasi_rekap.keluar], ['− Meninggal', d.mutasi_rekap.meninggal], ['Warga aktif akhir bulan', d.mutasi_rekap.saldo_akhir],
      ['Surat masuk', r.surat_masuk], ['Surat keluar diterbitkan', r.surat_keluar], ['Dokumen arsip baru', r.arsip_baru],
    ],
    bagian: [
      {
        judul: 'Mutasi Penduduk',
        kolom: [{ header: 'Tanggal', width: 13, tipe: 'tanggal' }, { header: 'Jenis', width: 16 }, { header: 'Nama', width: 28 }, { header: 'Keterangan', width: 44 }],
        baris: d.mutasi.map((x) => [x.tanggal, x.jenis, x.nama, x.keterangan]),
      },
      {
        judul: 'Rekap Layanan',
        kolom: [{ header: 'Jenis layanan', width: 28 }, { header: 'Total', width: 9, tipe: 'angka' }, { header: 'Menunggu', width: 11, tipe: 'angka' }, { header: 'Diproses', width: 11, tipe: 'angka' }, { header: 'Selesai', width: 10, tipe: 'angka' }, { header: 'Ditolak', width: 10, tipe: 'angka' }],
        baris: d.rekap_layanan.map((x) => [x.layanan, x.total, x['Menunggu Verifikasi'], x.Diproses, x.Selesai, x.Ditolak]),
      },
      {
        judul: 'Daftar Permohonan',
        kolom: [{ header: 'Kode', width: 15 }, { header: 'Tanggal', width: 13, tipe: 'tanggal' }, { header: 'Pemohon', width: 24 }, { header: 'Layanan', width: 24 }, { header: 'Status', width: 19 }, { header: 'Sumber', width: 11 }, { header: 'Selesai', width: 13, tipe: 'tanggal' }, { header: 'Catatan', width: 34 }],
        baris: d.permohonan.map((x) => [x.kode, x.tanggal, x.pemohon, x.layanan, x.status, x.sumber, x.selesai, x.catatan]),
      },
      {
        judul: 'Surat Masuk',
        kolom: [{ header: 'Nomor surat', width: 22 }, { header: 'Tanggal', width: 13, tipe: 'tanggal' }, { header: 'Pengirim', width: 28 }, { header: 'Perihal', width: 40 }, { header: 'Status', width: 13 }],
        baris: d.surat_masuk.map((x) => [x.nomor, x.tanggal, x.pihak, x.perihal, x.status]),
      },
      {
        judul: 'Surat Keluar',
        kolom: [{ header: 'Nomor surat', width: 24 }, { header: 'Tanggal', width: 13, tipe: 'tanggal' }, { header: 'Tujuan', width: 28 }, { header: 'Perihal', width: 40 }, { header: 'Status', width: 13 }],
        baris: d.surat_keluar.map((x) => [x.nomor, x.tanggal, x.pihak, x.perihal, x.status]),
      },
    ],
  }
}

async function laporanWarga() {
  const { data: res } = await api.get('/laporan/warga', { params: { nik: nikLengkap.value ? 1 : 0 } })
  const d = res.data
  const r = d.ringkasan
  return {
    berkas: 'daftar-warga',
    judul: 'Daftar Warga RT 03 / RW 20',
    periode: `Per ${formatTanggal(new Date().toISOString().slice(0, 10))}`,
    lanskap: true,
    ringkasan: [['Total tercatat', r.total], ['Berstatus aktif', r.aktif], ['Laki-laki (aktif)', r.laki_laki], ['Perempuan (aktif)', r.perempuan], ...Object.entries(r.umur).map(([k, v]) => [`Usia ${k} (aktif)`, v])],
    bagian: [{
      judul: 'Data Warga',
      kolom: [{ header: 'Nama', width: 24 }, { header: d.nik_lengkap ? 'NIK' : 'NIK (disamarkan)', width: 20 }, { header: 'L/P', width: 6, align: 'center' }, { header: 'Tgl lahir', width: 13, tipe: 'tanggal' }, { header: 'Umur', width: 7, tipe: 'angka' }, { header: 'Pekerjaan', width: 18 }, { header: 'Telepon', width: 16 }, { header: 'Status', width: 10 }, { header: 'Kepala keluarga', width: 22 }, { header: 'Hubungan', width: 14 }, { header: 'Alamat', width: 22 }],
      baris: d.baris.map((x) => [x.nama, x.nik, x.jenis_kelamin === 'Laki-laki' ? 'L' : 'P', x.tanggal_lahir, x.umur, x.pekerjaan, x.telepon, x.status, x.kepala_keluarga, x.hubungan, x.alamat]),
    }],
  }
}

async function laporanKeluarga() {
  const d = (await api.get('/laporan/keluarga')).data.data
  return {
    berkas: 'daftar-keluarga',
    judul: 'Daftar Keluarga RT 03 / RW 20',
    periode: `Per ${formatTanggal(new Date().toISOString().slice(0, 10))}`,
    ringkasan: [['Jumlah keluarga (KK)', d.ringkasan.total], ['Anggota terhubung ke KK', d.ringkasan.anggota]],
    bagian: [{
      judul: 'Data Keluarga',
      kolom: [{ header: 'Kepala keluarga', width: 26 }, { header: 'No. KK', width: 20 }, { header: 'Alamat', width: 26 }, { header: 'Status rumah', width: 16 }, { header: 'Anggota', width: 10, tipe: 'angka' }],
      baris: d.baris.map((x) => [x.kepala_keluarga, x.no_kk, x.alamat, x.status_rumah, x.anggota]),
    }],
  }
}

/** Jalankan: susun laporan lalu unduh Excel / cetak PDF. */
async function jalankan(kunci, aksi, susun) {
  sibuk.value = `${kunci}-${aksi}`
  try {
    if (aksi === 'pdf') {
      // cetakLaporan membuka jendelanya lebih dulu (masih dalam klik), baru memanggil `susun`.
      const hasil = await cetakLaporan(susun)
      if (!hasil.ok) ui.error(hasil.reason === 'popup' ? 'Pop-up diblokir browser. Izinkan pop-up untuk situs ini lalu coba lagi.' : 'Gagal menyiapkan laporan. Coba lagi.')
    } else {
      await unduhExcel(await susun())
      ui.success('Berkas Excel berhasil dibuat.')
    }
  } catch (e) {
    ui.error(e.response ? errorMessage(e) : 'Gagal menyiapkan laporan. Coba lagi.')
  } finally {
    sibuk.value = ''
  }
}
const ada = (kunci, aksi) => sibuk.value === `${kunci}-${aksi}`
</script>

<template>
  <PageHeader title="Laporan & Ekspor" description="Unduh data RT sebagai Excel atau cetak/simpan sebagai PDF. Cocok untuk laporan ke RW/kelurahan dan arsip." />

  <div class="stack">
    <!-- Laporan bulanan -->
    <section class="card">
      <div class="card-head">
        <div><h3><CalendarRange :size="18" /> Laporan Bulanan</h3><p>Ringkasan kegiatan, rekap layanan, daftar permohonan, serta surat masuk &amp; keluar dalam satu bulan.</p></div>
        <input v-model="bulan" type="month" class="input bulan" aria-label="Pilih bulan" :max="new Date().toISOString().slice(0, 7)">
      </div>
      <div class="card-body">
        <div v-if="loading" class="loading" style="padding:24px"><div class="spinner" /></div>
        <template v-else-if="R">
          <div class="kpis">
            <div><b>{{ R.permohonan }}</b><span>Permohonan</span></div>
            <div><b>{{ R.selesai }}</b><span>Selesai</span></div>
            <div><b>{{ R.ditolak }}</b><span>Ditolak</span></div>
            <div><b>{{ R.belum_selesai }}</b><span>Belum selesai</span></div>
            <div><b>{{ R.surat_masuk }}</b><span>Surat masuk</span></div>
            <div><b>{{ R.surat_keluar }}</b><span>Surat keluar</span></div>
          </div>
          <p v-if="!R.permohonan && !R.surat_masuk && !R.surat_keluar" class="muted small" style="margin-top:12px">Belum ada kegiatan tercatat pada {{ namaBulan(bulan) }}.</p>
          <div class="row row-wrap" style="margin-top:16px">
            <button class="btn btn-primary" :disabled="!!sibuk" @click="jalankan('bln', 'xlsx', async () => laporanBulanan())"><FileSpreadsheet :size="17" /> {{ ada('bln', 'xlsx') ? 'Membuat…' : 'Unduh Excel' }}</button>
            <button class="btn btn-secondary" :disabled="!!sibuk" @click="jalankan('bln', 'pdf', async () => laporanBulanan())"><Printer :size="17" /> Cetak / simpan PDF</button>
          </div>
        </template>
      </div>
    </section>

    <div class="grid grid-2">
      <!-- Warga -->
      <section class="card">
        <div class="card-head"><div><h3><Users :size="18" /> Daftar Warga</h3><p>Seluruh warga beserta umur, pekerjaan, dan keluarganya.</p></div></div>
        <div class="card-body">
          <label class="cek"><input v-model="nikLengkap" type="checkbox"> Sertakan <b>NIK lengkap</b></label>
          <div v-if="nikLengkap" class="callout tone-warn" style="margin-top:10px"><ShieldAlert :size="18" /><span>Berkas akan memuat NIK seluruh warga. Simpan dengan hati-hati dan jangan dikirim lewat grup. Pengunduhan ini <b>tercatat di log akses NIK</b>.</span></div>
          <p v-else class="muted small" style="margin-top:6px">Tanpa centang, NIK disamarkan (327***…) — aman dibagikan.</p>
          <div class="row row-wrap" style="margin-top:14px">
            <button class="btn btn-primary" :disabled="!!sibuk" @click="jalankan('wrg', 'xlsx', laporanWarga)"><FileSpreadsheet :size="17" /> {{ ada('wrg', 'xlsx') ? 'Membuat…' : 'Unduh Excel' }}</button>
            <button class="btn btn-secondary" :disabled="!!sibuk" @click="jalankan('wrg', 'pdf', laporanWarga)"><Printer :size="17" /> Cetak / PDF</button>
          </div>
        </div>
      </section>

      <!-- Keluarga -->
      <section class="card">
        <div class="card-head"><div><h3><UsersRound :size="18" /> Daftar Keluarga</h3><p>Kepala keluarga, No. KK, alamat, dan jumlah anggota.</p></div></div>
        <div class="card-body">
          <p class="muted small">No. KK dicantumkan lengkap. Hanya Ketua RT, Sekretaris, dan Administrator yang dapat mengunduh laporan ini.</p>
          <div class="row row-wrap" style="margin-top:14px">
            <button class="btn btn-primary" :disabled="!!sibuk" @click="jalankan('kk', 'xlsx', laporanKeluarga)"><FileSpreadsheet :size="17" /> {{ ada('kk', 'xlsx') ? 'Membuat…' : 'Unduh Excel' }}</button>
            <button class="btn btn-secondary" :disabled="!!sibuk" @click="jalankan('kk', 'pdf', laporanKeluarga)"><Printer :size="17" /> Cetak / PDF</button>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.card-head h3 { display: flex; align-items: center; gap: 8px; }
.bulan { width: auto; }
.kpis { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; }
.kpis > div { background: var(--surface-2); border-radius: 12px; padding: 12px 14px; }
.kpis b { display: block; font-size: 24px; letter-spacing: -.02em; }
.kpis span { font-size: 12px; color: var(--muted); }
.cek { display: flex; gap: 8px; align-items: center; font-weight: 600; }
.cek input { width: 18px; height: 18px; accent-color: var(--primary); }
@media (max-width: 900px) { .kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 480px) { .kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>
