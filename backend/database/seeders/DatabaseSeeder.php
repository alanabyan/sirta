<?php

namespace Database\Seeders;

use App\Models\Aktivitas;
use App\Models\Arsip;
use App\Models\Keluarga;
use App\Models\Pengajuan;
use App\Models\Pengurus;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\TemplateSurat;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->pengguna();
        $kk = $this->keluarga();
        $warga = $this->warga($kk);
        $this->pengurus();
        $templates = $this->templates();
        $this->surat($templates);
        $this->arsip();
        $this->pengajuan($warga);
    }

    private function pengguna(): void
    {
        foreach ([
            ['Admin RT', 'admin', 'administrator'],
            ['Wiyadi', 'ketua.rt', 'ketua_rt'],
            ['H. Suryana', 'sekretaris', 'sekretaris'],
            ['Nina Kurnia', 'bendahara', 'bendahara'],
            ['Agus Salim', 'operator', 'operator'],
        ] as $i => [$name, $username, $role]) {
            User::create([
                'name' => $name, 'username' => $username, 'role' => $role,
                'password' => 'password', 'last_login_at' => now()->subHours($i * 7 + 1),
            ]);
        }
    }

    private function keluarga(): array
    {
        $rows = [
            'fauzan' => ['3275010101001001', 'Ahmad Fauzan', 'Blok C1 No. 12', 'Milik sendiri'],
            'budi' => ['3275010101001002', 'Budi Santoso', 'Blok C2 No. 08', 'Milik sendiri'],
            'dedi' => ['3275010101001003', 'Dedi Kurnia', 'Blok B4 No. 15', 'Kontrak'],
            'agus' => ['3275010101001004', 'Agus Salim', 'Blok A2 No. 07', 'Milik sendiri'],
            'hendra' => ['3275010101001005', 'Hendra Wijaya', 'Blok D1 No. 03', 'Milik sendiri'],
        ];
        $out = [];
        foreach ($rows as $key => [$no, $kepala, $alamat, $status]) {
            $out[$key] = Keluarga::create(['no_kk' => $no, 'kepala_keluarga' => $kepala, 'alamat' => $alamat, 'status_rumah' => $status])->id;
        }

        return $out;
    }

    private function warga(array $kk): array
    {
        $rows = [
            ['3275011201950001', 'Ahmad Fauzan', 'Laki-laki', 31, 'Karyawan Swasta', 'fauzan', 'Kepala Keluarga'],
            ['3275015403920002', 'Siti Nurhayati', 'Perempuan', 34, 'Ibu Rumah Tangga', 'fauzan', 'Istri'],
            ['3275012206870003', 'Budi Santoso', 'Laki-laki', 39, 'Wiraswasta', 'budi', 'Kepala Keluarga'],
            ['3275016508010004', 'Rina Marlina', 'Perempuan', 25, 'Guru', 'budi', 'Famili Lain'],
            ['3275014304770005', 'Dedi Kurnia', 'Laki-laki', 49, 'Pensiunan', 'dedi', 'Kepala Keluarga'],
            ['3275015909140006', 'Nadia Putri', 'Perempuan', 20, 'Mahasiswa', 'dedi', 'Anak'],
            ['3275011002100007', 'Rizky Pratama', 'Laki-laki', 16, 'Pelajar', 'dedi', 'Anak'],
            ['3275014701190008', 'Lestari Wulandari', 'Perempuan', 55, 'Pedagang', 'dedi', 'Istri'],
            ['3275012808820009', 'Agus Salim', 'Laki-laki', 44, 'Teknisi', 'agus', 'Kepala Keluarga'],
            ['3275016207930010', 'Dian Puspita', 'Perempuan', 33, 'Perawat', 'agus', 'Istri'],
            ['3275010703650011', 'Hendra Wijaya', 'Laki-laki', 61, 'Pensiunan', 'hendra', 'Kepala Keluarga'],
            ['3275014805960012', 'Maya Sari', 'Perempuan', 30, 'Desainer', null, null],
        ];
        $ids = [];
        foreach ($rows as $i => [$nik, $nama, $jk, $umur, $job, $k, $hub]) {
            $ids[] = Warga::create([
                'nik' => $nik, 'nama' => $nama, 'jenis_kelamin' => $jk,
                'tanggal_lahir' => now()->subYears($umur)->subDays(20 + $i * 17)->toDateString(),
                'pekerjaan' => $job, 'keluarga_id' => $k ? $kk[$k] : null, 'hubungan_keluarga' => $hub,
                'telepon' => '0812-3000-'.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT), 'status' => 'Aktif',
            ])->id;
        }

        return $ids;
    }

    private function pengurus(): void
    {
        foreach ([
            ['Wiyadi', 'Ketua RT'], ['H. Suryana', 'Sekretaris'], ['Nina Kurnia', 'Bendahara'],
            ['Agus Salim', 'Seksi Pelayanan'], ['Rina Marlina', 'Seksi Data'], ['Dedi Kurnia', 'Seksi Sosial'],
        ] as $i => [$nama, $jabatan]) {
            Pengurus::create(['nama' => $nama, 'jabatan' => $jabatan, 'periode' => '2025–2028', 'kontak' => '0812-0000-100'.($i + 1)]);
        }
    }

    private function templates(): array
    {
        $kop = "RUKUN TETANGGA 03 / RUKUN WARGA 20\nPerumahan Griya Kreasi Aqilla\nDesa Sukajaya, Kecamatan Cibitung, Kabupaten Bekasi";
        $ttd = "Bekasi, {{tanggal}}\nKetua RT 03\n\n\n\nWiyadi";
        $rows = [
            ['SK-PENGANTAR', 'Surat Pengantar', 'Pengantar administrasi warga ke instansi lain',
                "$kop\n\nSURAT PENGANTAR\nNomor: {{nomor}}\n\nYang bertanda tangan di bawah ini, Ketua RT 03 RW 20, menerangkan bahwa:\n\nNama : {{nama}}\nNIK : {{nik}}\nAlamat : {{alamat}}\n\nAdalah benar warga kami dan bermaksud mengurus: {{keperluan}}.\n\nDemikian surat pengantar ini dibuat untuk dipergunakan sebagaimana mestinya.\n\n$ttd"],
            ['SK-DOMISILI', 'Surat Domisili', 'Keterangan bertempat tinggal di lingkungan RT',
                "$kop\n\nSURAT KETERANGAN DOMISILI\nNomor: {{nomor}}\n\nMenerangkan bahwa {{nama}} (NIK {{nik}}) benar berdomisili di {{alamat}}, wilayah RT 03 / RW 20.\n\nKeperluan: {{keperluan}}.\n\nDemikian surat keterangan ini dibuat dengan sebenarnya.\n\n$ttd"],
            ['SK-KET', 'Surat Keterangan', 'Keterangan umum dari RT',
                "$kop\n\nSURAT KETERANGAN\nNomor: {{nomor}}\n\nMenerangkan bahwa {{nama}} (NIK {{nik}}), beralamat di {{alamat}}, adalah warga RT 03 / RW 20.\n\nSurat ini dibuat untuk keperluan: {{keperluan}}.\n\n$ttd"],
            ['SK-UND', 'Surat Undangan', 'Undangan kegiatan atau rapat warga',
                "$kop\n\nUNDANGAN\nNomor: {{nomor}}\n\nKepada Yth. Bapak/Ibu Warga RT 03\n\nDiharapkan kehadiran Bapak/Ibu pada kegiatan: {{keperluan}}.\n\nDemikian undangan ini disampaikan, atas perhatian dan kehadirannya diucapkan terima kasih.\n\n$ttd"],
            ['SK-NIKAH', 'Surat Pengantar Nikah', 'Pengantar administrasi pernikahan',
                "$kop\n\nSURAT PENGANTAR NIKAH\nNomor: {{nomor}}\n\nMenerangkan bahwa {{nama}} (NIK {{nik}}), beralamat di {{alamat}}, adalah warga RT 03 / RW 20 dan bermaksud melangsungkan pernikahan.\n\nDemikian surat pengantar ini dibuat untuk dipergunakan sebagaimana mestinya.\n\n$ttd"],
            ['SK-USAHA', 'Surat Keterangan Usaha', 'Keterangan usaha milik warga',
                "$kop\n\nSURAT KETERANGAN USAHA\nNomor: {{nomor}}\n\nMenerangkan bahwa {{nama}} (NIK {{nik}}), beralamat di {{alamat}}, memiliki usaha: {{keperluan}}.\n\nDemikian surat keterangan ini dibuat dengan sebenarnya.\n\n$ttd"],
        ];
        $out = [];
        foreach ($rows as [$kode, $nama, $desk, $isi]) {
            $out[$kode] = TemplateSurat::create(compact('kode', 'nama', 'isi') + ['deskripsi' => $desk])->id;
        }

        return $out;
    }

    private function surat(array $tpl): void
    {
        foreach ([
            ['SM-034/IX/2026', 0, 'Desa Sukajaya', 'Undangan Musyawarah Desa', 'Baru'],
            ['SM-033/IX/2026', 4, 'Kecamatan Cibitung', 'Informasi Pendataan Warga', 'Diproses'],
            ['SM-032/IX/2026', 10, 'Puskesmas Cibitung', 'Jadwal Posyandu', 'Diarsipkan'],
            ['SM-031/IX/2026', 15, 'RW 20', 'Koordinasi Keamanan Lingkungan', 'Diarsipkan'],
        ] as [$no, $ago, $dari, $hal, $st]) {
            SuratMasuk::create(['nomor' => $no, 'tanggal' => now()->subDays($ago + 1), 'pengirim' => $dari, 'perihal' => $hal, 'status' => $st]);
        }

        foreach ([
            ['SK-041/RT03/X/2026', 0, 'Desa Sukajaya', 'Pengantar Administrasi Warga', 'SK-PENGANTAR'],
            ['SK-040/RT03/X/2026', 2, 'Kecamatan Cibitung', 'Laporan Kegiatan RT', 'SK-KET'],
            ['SK-039/RT03/IX/2026', 9, 'Warga RT 03', 'Undangan Kerja Bakti', 'SK-UND'],
        ] as [$no, $ago, $tujuan, $hal, $t]) {
            SuratKeluar::create([
                'nomor' => $no, 'tanggal' => now()->subDays($ago), 'tujuan' => $tujuan, 'perihal' => $hal,
                'status' => 'Diterbitkan', 'template_surat_id' => $tpl[$t],
                'isi' => TemplateSurat::find($tpl[$t])->isi,
            ]);
        }
    }

    private function arsip(): void
    {
        foreach ([
            ['Surat Pengantar Ahmad Fauzan.pdf', 'Surat', 0, 284 * 1024, 1],
            ['Daftar Warga RT 03.xlsx', 'Kependudukan', 2, 62 * 1024, 1],
            ['SK Kepengurusan RT 03.pdf', 'Kependudukan', 8, 512 * 1024, 3],
            ['Undangan Kerja Bakti.pdf', 'Kegiatan', 9, 188 * 1024, 3],
            ['Laporan Kas Bulanan.pdf', 'Keuangan', 12, 220 * 1024, 4],
            ['Dokumentasi Rapat RT.zip', 'Kegiatan', 19, 8_800_000, 1],
        ] as [$nama, $kat, $ago, $size, $uid]) {
            Arsip::create(['nama' => $nama, 'kategori' => $kat, 'ukuran' => $size, 'user_id' => $uid, 'created_at' => now()->subDays($ago)]);
        }
    }

    private function pengajuan(array $warga): void
    {
        $layanan = ['Surat Pengantar', 'Surat Domisili', 'Surat Keterangan', 'Surat Keterangan Usaha'];

        // Riwayat 5 bulan sebelumnya (untuk grafik tren).
        foreach ([12, 18, 15, 24, 20] as $i => $jumlah) {
            $bulan = now()->startOfMonth()->subMonths(5 - $i);
            for ($n = 0; $n < $jumlah; $n++) {
                $tgl = $bulan->copy()->addDays($n % 27)->setTime(8 + $n % 8, 15);
                $p = Pengajuan::create([
                    'kode' => sprintf('PL-%d-%04d', $tgl->year, 900 + $i * 30 + $n),
                    'warga_id' => $warga[$n % count($warga)], 'layanan' => $layanan[$n % 4],
                    'keperluan' => 'Keperluan administrasi', 'status' => 'Selesai', 'created_at' => $tgl, 'updated_at' => $tgl,
                ]);
                $p->riwayat()->createMany([
                    ['status' => 'Menunggu Verifikasi', 'catatan' => 'Pengajuan dibuat.', 'created_at' => $tgl, 'updated_at' => $tgl],
                    ['status' => 'Diproses', 'created_at' => $tgl, 'updated_at' => $tgl],
                    ['status' => 'Selesai', 'created_at' => $tgl, 'updated_at' => $tgl],
                ]);
            }
        }

        // Pengajuan bulan ini — beragam status untuk demonstrasi alur.
        foreach ([
            [1, 'Ahmad Fauzan', 0, 'Surat Pengantar', 'Selesai', 'Pengantar mengurus KTP-el baru'],
            [2, 'Siti Nurhayati', 1, 'Surat Domisili', 'Diproses', 'Syarat pendaftaran sekolah anak'],
            [3, 'Nadia Putri', 5, 'Surat Keterangan', 'Menunggu Verifikasi', 'Persyaratan beasiswa kampus'],
            [4, 'Agus Salim', 8, 'Surat Pengantar', 'Ditolak', 'Pengantar pindah domisili'],
            [5, 'Rina Marlina', 3, 'Surat Domisili', 'Diproses', 'Melamar pekerjaan'],
            [6, 'Dedi Kurnia', 2, 'Surat Keterangan', 'Selesai', 'Pengajuan bantuan sosial'],
            [7, 'Maya Sari', 11, 'Surat Keterangan Usaha', 'Menunggu Verifikasi', 'Usaha desain grafis rumahan'],
        ] as [$no, , $wi, $svc, $status, $perlu]) {
            $tgl = now()->subDays(7 - $no)->setTime(9, 30);
            $p = Pengajuan::create([
                'kode' => sprintf('PL-%d-%04d', now()->year, $no + 36), 'warga_id' => $warga[$wi], 'layanan' => $svc,
                'keperluan' => $perlu, 'status' => $status, 'created_at' => $tgl, 'updated_at' => $tgl,
                'catatan' => $status === 'Ditolak' ? 'Dokumen KK belum dilampirkan. Silakan ajukan ulang.' : null,
            ]);
            $p->riwayat()->create(['status' => 'Menunggu Verifikasi', 'catatan' => 'Pengajuan dibuat.', 'user_id' => 5, 'created_at' => $tgl, 'updated_at' => $tgl]);
            if (in_array($status, ['Diproses', 'Selesai'])) {
                $p->riwayat()->create(['status' => 'Diproses', 'catatan' => 'Data lengkap, surat sedang disiapkan.', 'user_id' => 3, 'created_at' => $tgl->copy()->addHours(3), 'updated_at' => $tgl]);
            }
            if ($status === 'Selesai') {
                $p->riwayat()->create(['status' => 'Selesai', 'catatan' => 'Surat dapat diambil di sekretariat RT.', 'user_id' => 3, 'created_at' => $tgl->copy()->addDay(), 'updated_at' => $tgl]);
            }
            if ($status === 'Ditolak') {
                $p->riwayat()->create(['status' => 'Ditolak', 'catatan' => $p->catatan, 'user_id' => 3, 'created_at' => $tgl->copy()->addHours(5), 'updated_at' => $tgl]);
            }
        }

        foreach ([
            ['Ahmad Fauzan menyelesaikan pengajuan PL-2026-0037', 'check', 3],
            ['Arsip “Surat Pengantar Ahmad Fauzan.pdf” ditambahkan', 'archive', 25],
            ['Surat SK-041/RT03/X/2026 diterbitkan', 'plus', 55],
            ['Data warga diperbarui oleh Admin RT', 'edit', 90],
        ] as [$d, $ikon, $min]) {
            Aktivitas::create(['user_id' => 1, 'deskripsi' => $d, 'ikon' => $ikon, 'created_at' => now()->subMinutes($min), 'updated_at' => now()]);
        }
    }
}
