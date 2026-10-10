/**
 * Isi panduan awal (guided tour).
 *
 * Prinsip: panduan HANYA menyebut fitur yang memang ada di layar pengguna itu.
 *  - Langkah menu diambil dari menu yang benar-benar dirender (`[data-tour="nav-/jalur"]`). Menu sendiri sudah
 *    disaring menurut peran di AppLayout.vue, jadi fitur yang tak boleh diakses otomatis tidak muncul di sini.
 *  - Teks tiap langkah disesuaikan dengan kemampuan peran (`can`), bukan sekadar nama peran.
 */

const PROFIL = {
  administrator: {
    sapa: 'Anda masuk sebagai <b>Administrator</b>: akses penuh ke seluruh fitur, termasuk mengelola pengguna dan identitas RT.',
    mulai: 'Saran langkah pertama: buka <b>Pengguna Sistem</b> untuk menambah akun pengurus lain, lalu isi <b>Identitas &amp; Tanda Tangan</b>.',
  },
  ketua_rt: {
    sapa: 'Anda masuk sebagai <b>Ketua RT</b>: Anda memutuskan permohonan warga, menerbitkan surat, dan mengatur tanda tangan serta stempel RT.',
    mulai: 'Saran langkah pertama: isi <b>Identitas &amp; Tanda Tangan</b>, lalu periksa antrean di <b>Verifikasi</b>.',
  },
  sekretaris: {
    sapa: 'Anda masuk sebagai <b>Sekretaris</b>: Anda memverifikasi permohonan, menerbitkan surat, dan mengelola data induk serta laporan.',
    mulai: 'Saran langkah pertama: buka <b>Verifikasi</b> untuk melihat permohonan yang menunggu keputusan.',
  },
  operator: {
    sapa: 'Anda masuk sebagai <b>Operator</b>: Anda membantu mencatat data warga, permohonan, dan menyiapkan surat. Keputusan akhir ada pada Ketua RT atau Sekretaris.',
    mulai: 'Saran langkah pertama: buka <b>Permohonan Warga</b> untuk mencatat permohonan yang masuk.',
  },
  bendahara: {
    sapa: 'Anda masuk sebagai <b>Bendahara</b>: Anda dapat <b>melihat</b> data dan layanan RT, tetapi tidak mengubahnya.',
    mulai: 'Saran langkah pertama: lihat ringkasan di <b>Beranda</b> atau buka <b>Permohonan Warga</b> untuk memantau layanan.',
  },
}

/** Urutan & isi langkah menu. `teks(can)` mengembalikan penjelasan sesuai kemampuan pengguna. */
export const LANGKAH_MENU = [
  {
    jalur: '/laporan',
    judul: 'Laporan & Ekspor',
    ringkas: 'Unduh laporan Excel atau cetak PDF',
    teks: () =>
      'Unduh <b>Laporan Bulanan</b>, Daftar Warga, dan Daftar Keluarga sebagai <b>Excel</b>, atau cetak sebagai <b>PDF</b> — siap dilaporkan ke RW/kelurahan. NIK disamarkan kecuali Anda sengaja mencentangnya (dan itu tercatat).',
  },
  {
    jalur: '/layanan',
    judul: 'Permohonan Warga',
    ringkas: 'Permohonan surat dari warga',
    teks: (c) =>
      c.tulis
        ? 'Catat permohonan surat dari warga — atau lihat yang diajukan warga sendiri lewat halaman <b>/ajukan</b> — dan pantau tahapnya: <b>Menunggu → Diproses → Selesai</b>.'
        : 'Lihat semua permohonan warga beserta tahap dan statusnya.',
  },
  {
    jalur: '/verifikasi',
    judul: 'Verifikasi',
    ringkas: 'Antrean permohonan',
    teks: (c) =>
      c.putuskan
        ? 'Antrean permohonan yang menunggu <b>keputusan Anda</b>. Periksa data dan lampiran, lalu <b>Setujui</b> atau <b>Tolak</b> (alasan wajib, dan terlihat oleh warga). Angka pada menu = jumlah yang menunggu.'
        : 'Antrean permohonan yang menunggu keputusan. Anda dapat melihat detailnya; <b>keputusan</b> diambil oleh Ketua RT, Sekretaris, atau Administrator.',
  },
  {
    jalur: '/tracking',
    judul: 'Lacak Permohonan',
    ringkas: 'Cari permohonan dengan kodenya',
    teks: () =>
      'Cari satu permohonan dengan kodenya (mis. <b>PL-2026-0042</b>) untuk melihat tahap dan riwayatnya. Warga juga bisa melacak sendiri di halaman <b>/lacak</b> memakai kode + 4 digit NIK.',
  },
  {
    jalur: '/surat-masuk',
    judul: 'Surat Masuk',
    ringkas: 'Surat yang diterima RT',
    teks: (c) =>
      c.tulis
        ? 'Catat setiap surat yang diterima RT, ubah statusnya (<b>Baru → Diproses → Diarsipkan</b>), dan cari kembali kapan saja.'
        : 'Lihat daftar surat yang diterima RT beserta statusnya.',
  },
  {
    jalur: '/surat-keluar',
    judul: 'Surat Keluar',
    ringkas: 'Surat yang diterbitkan RT',
    teks: (c) =>
      c.putuskan
        ? 'Buat surat dari template — nomor terisi otomatis. <b>Terbitkan</b> agar tercetak dengan tanda tangan, stempel, dan <b>kode QR</b> keaslian. Surat berstatus Draf tercetak dengan watermark.'
        : c.tulis
          ? 'Siapkan surat dari template sebagai <b>Draf</b>. Penerbitan (tanda tangan, stempel, kode QR) dilakukan oleh Ketua RT atau Sekretaris.'
          : 'Lihat daftar surat keluar yang sudah dibuat RT.',
  },
  {
    jalur: '/template',
    judul: 'Template Surat',
    ringkas: 'Kerangka surat siap pakai',
    teks: (c) =>
      c.putuskan
        ? 'Kerangka surat siap pakai. Anda dapat menambah dan mengubah template; isian seperti <b>{{nama}}</b> dan <b>{{nik}}</b> terisi otomatis dari data warga.'
        : 'Lihat kerangka surat yang tersedia dan isian otomatisnya.',
  },
  {
    jalur: '/arsip',
    judul: 'Arsip Digital',
    ringkas: 'Penyimpanan dokumen',
    teks: (c) =>
      c.tulis
        ? 'Simpan dokumen RT (PDF, Word, Excel, gambar, ZIP — maks. 10 MB) per kategori dan unduh kembali kapan saja.'
        : 'Lihat dan unduh dokumen yang tersimpan di arsip RT.',
  },
  {
    jalur: '/warga',
    judul: 'Data Warga',
    ringkas: 'Data penduduk RT',
    teks: (c) =>
      c.tulis
        ? 'Data seluruh penduduk RT. NIK tampil <b>disamarkan</b>; NIK lengkap hanya muncul saat mengubah data atau menyusun surat, dan pembukaannya <b>tercatat</b>.'
        : 'Lihat data penduduk RT. NIK ditampilkan dalam bentuk disamarkan.',
  },
  {
    jalur: '/keluarga',
    judul: 'Data Keluarga',
    ringkas: 'Kartu Keluarga & anggotanya',
    teks: (c) =>
      c.tulis
        ? 'Kelola Kartu Keluarga (KK) dan lihat anggotanya. Hubungkan warga ke KK lewat <b>Data Warga</b>.'
        : 'Lihat Kartu Keluarga (KK) dan anggotanya.',
  },
  {
    jalur: '/mutasi',
    judul: 'Mutasi Warga',
    ringkas: 'Lahir, pindah, meninggal',
    teks: () =>
      'Catat <b>lahir, pindah masuk, pindah keluar, dan meninggal</b>. Status warga ikut berubah, saldo penduduk terhitung otomatis, dan rekapnya masuk ke laporan bulanan. Salah catat? Bisa dibatalkan.',
  },
  {
    jalur: '/pengurus',
    judul: 'Pengurus RT',
    ringkas: 'Susunan kepengurusan',
    teks: (c) =>
      c.putuskan ? 'Susunan pengurus RT. Anda dapat menambah, mengubah, dan menghapus pengurus.' : 'Lihat susunan pengurus RT.',
  },
  {
    jalur: '/pengaturan',
    judul: 'Identitas & Tanda Tangan',
    ringkas: 'Kop surat, tanda tangan, stempel',
    teks: () =>
      'Isi <b>kop surat</b> dan nama penandatangan, lalu unggah atau <b>gambar</b> tanda tangan dan stempel RT. Ini perlu diisi sebelum menerbitkan surat resmi.',
  },
  {
    jalur: '/pengguna',
    judul: 'Pengguna Sistem',
    ringkas: 'Akun & hak akses',
    teks: () =>
      'Tambah akun pengurus dan atur <b>peran</b>-nya — setiap peran punya hak berbeda. Di sini juga tercatat <b>log akses NIK</b>.',
  },
]

const ADA = (sel) => !!document.querySelector(sel)

// Gelar di depan nama tidak dipakai untuk menyapa: "H. Suryana" → "Suryana", bukan "H.".
const GELAR = /^(h|hj|haji|hajjah|kh|dr|drs|dra|ir|prof|ust|ustadz|ustadzah|bpk|bapak|pak|ibu|bu|sdr|sdri)[.,]?$/i
export function namaSapaan(nama = '') {
  const kata = String(nama).trim().split(/\s+/).filter(Boolean)
  return kata.find((k) => !GELAR.test(k)) || kata[0] || ''
}

/** Kemampuan pengguna — sama persis dengan yang menentukan menu & tombol di aplikasi. */
export const kemampuan = (auth) => ({
  tulis: auth.canWrite,
  putuskan: auth.canDecide,
  atur: auth.canSetting,
  admin: auth.isAdmin,
})

/**
 * @param {object} o
 * @param {object} o.auth     store auth (user, canWrite, canDecide, …)
 * @param {boolean} o.mobile  layar sempit: menu samping tertutup, jadi diringkas jadi satu langkah
 * @returns {import('driver.js').DriveStep[]}
 */
export function susunLangkah({ auth, mobile }) {
  const c = kemampuan(auth)
  const profil = PROFIL[auth.user.role] ?? PROFIL.operator
  const namaDepan = namaSapaan(auth.user.name)
  const langkah = []

  langkah.push({
    popover: {
      title: `Selamat datang, ${namaDepan}! 👋`,
      description: `${profil.sapa}<br><br>Panduan singkat ini (±1 menit) <b>hanya menunjukkan fitur yang bisa Anda gunakan</b>. Tekan <b>Lanjut</b>, atau <b>Esc</b> untuk melewatinya.`,
    },
  })

  // ---- Beranda (hanya bila pengguna sedang berada di Beranda) ----
  if (ADA('[data-tour="dash-sapaan"]')) {
    langkah.push({
      element: '[data-tour="dash-sapaan"]',
      popover: {
        title: c.tulis ? 'Aksi cepat' : 'Ringkasan hari ini',
        description: c.tulis
          ? 'Pintasan untuk pekerjaan yang paling sering: <b>buat permohonan</b>, tambah warga, buat surat keluar, atau catat surat masuk.'
          : 'Sapaan dan gambaran singkat administrasi RT hari ini.',
        side: 'bottom',
      },
    })
  }
  if (ADA('[data-tour="dash-perhatian"]')) {
    langkah.push({
      element: '[data-tour="dash-perhatian"]',
      popover: {
        title: 'Perlu perhatian Anda',
        description: c.putuskan
          ? 'Pekerjaan yang menunggu: permohonan yang harus Anda <b>verifikasi</b>, permohonan diproses yang <b>suratnya belum dibuat</b>, dan surat masuk baru. Klik untuk langsung ke halamannya.'
          : c.tulis
            ? 'Pekerjaan yang sedang menunggu di RT: permohonan baru dan surat masuk. Klik untuk membukanya. Keputusan verifikasi diambil Ketua RT atau Sekretaris.'
            : 'Gambaran pekerjaan yang sedang menunggu di RT. Klik untuk melihat daftarnya.',
        side: 'bottom',
      },
    })
  }
  if (ADA('[data-tour="dash-angka"]')) {
    langkah.push({
      element: '[data-tour="dash-angka"]',
      popover: {
        title: 'Angka utama',
        description: 'Warga aktif, jumlah KK, permohonan yang sedang berjalan, dan dokumen arsip. <b>Klik kartunya</b> untuk membuka daftar lengkapnya.',
        side: 'bottom',
      },
    })
  }
  if (ADA('[data-tour="dash-grafik"]')) {
    langkah.push({
      element: '[data-tour="dash-grafik"]',
      popover: {
        title: 'Tren layanan',
        description: 'Jumlah permohonan 6 bulan terakhir, dan pembagian status permohonan bulan ini.',
        side: 'top',
      },
    })
  }

  // ---- Menu ----
  const menuTersedia = LANGKAH_MENU.filter((m) => ADA(`[data-tour="nav-${m.jalur}"]`))

  if (mobile) {
    // Menu samping tertutup di layar sempit: satu langkah yang mendaftar fitur milik peran ini.
    langkah.push({
      element: '[data-tour="menu-btn"]',
      popover: {
        title: 'Menu fitur Anda',
        description:
          'Ketuk tombol ini untuk membuka menu. Fitur yang tersedia untuk Anda:<ul class="tour-daftar">' +
          menuTersedia.map((m) => `<li><b>${m.judul}</b> — ${m.ringkas}</li>`).join('') +
          '</ul>',
        side: 'bottom',
      },
    })
  } else {
    if (ADA('[data-tour="sidebar"]')) {
      langkah.push({
        element: '[data-tour="sidebar"]',
        popover: {
          title: 'Menu utama',
          description: `Semua fitur untuk peran Anda ada di sini, dikelompokkan per tugas. Ada <b>${menuTersedia.length} fitur</b> yang bisa Anda gunakan — kita lihat satu per satu. Klik judul kelompok untuk membuka atau menutupnya.`,
          side: 'right',
          align: 'start',
        },
      })
    }
    for (const m of menuTersedia) {
      langkah.push({
        element: `[data-tour="nav-${m.jalur}"]`,
        popover: { title: m.judul, description: m.teks(c), side: 'right', align: 'center' },
      })
    }
  }

  // ---- Bilah atas ----
  if (ADA('[data-tour="tema"]')) {
    langkah.push({
      element: '[data-tour="tema"]',
      popover: { title: 'Mode terang / gelap', description: 'Ganti tampilan agar nyaman di mata, terutama saat malam hari. Pilihan Anda diingat.', side: 'bottom', align: 'end' },
    })
  }
  if (ADA('[data-tour="pengguna"]')) {
    langkah.push({
      element: '[data-tour="pengguna"]',
      popover: {
        title: 'Akun Anda',
        description: 'Di sini Anda bisa <b>mengubah kata sandi</b>, <b>keluar</b>, dan <b>mengulang panduan ini</b> kapan saja.',
        side: 'bottom',
        align: 'end',
      },
    })
  }

  langkah.push({
    popover: {
      title: 'Selesai! 🎉',
      description: `${profil.mulai}<br><br>Mau melihat panduan ini lagi? Buka menu nama Anda → <b>Ulangi panduan</b>.`,
    },
  })

  return langkah
}
