<?php

namespace Database\Seeders;

use App\Models\TemplateSurat;
use Illuminate\Database\Seeder;

/**
 * Template surat bawaan. Aman dijalankan berulang: template yang sudah ada (menurut kode)
 * tidak ditimpa, sehingga perubahan yang dibuat pengurus tetap terjaga.
 */
class TemplateSuratSeeder extends Seeder
{
    public function run(): void
    {
        $this->buat();
    }

    /** @return array<string,int> kode template → id */
    public function buat(): array
    {
        $rows = [
            ['SK-PENGANTAR', 'Surat Pengantar', 'Pengantar administrasi warga ke instansi lain',
                "SURAT PENGANTAR\nNomor: {{nomor}}\n\nYang bertanda tangan di bawah ini, Ketua RT 03 RW 20, menerangkan bahwa:\n\nNama : {{nama}}\nNIK : {{nik}}\nAlamat : {{alamat}}\n\nAdalah benar warga kami dan bermaksud mengurus: {{keperluan}}.\n\nDemikian surat pengantar ini dibuat untuk dipergunakan sebagaimana mestinya.\n\n{{ttd}}"],
            ['SK-DOMISILI', 'Surat Domisili', 'Keterangan bertempat tinggal di lingkungan RT',
                "SURAT KETERANGAN DOMISILI\nNomor: {{nomor}}\n\nMenerangkan bahwa {{nama}} (NIK {{nik}}) benar berdomisili di {{alamat}}, wilayah RT 03 / RW 20.\n\nKeperluan: {{keperluan}}.\n\nDemikian surat keterangan ini dibuat dengan sebenarnya.\n\n{{ttd}}"],
            ['SK-KET', 'Surat Keterangan', 'Keterangan umum dari RT',
                "SURAT KETERANGAN\nNomor: {{nomor}}\n\nMenerangkan bahwa {{nama}} (NIK {{nik}}), beralamat di {{alamat}}, adalah warga RT 03 / RW 20.\n\nSurat ini dibuat untuk keperluan: {{keperluan}}.\n\n{{ttd}}"],
            ['SK-UND', 'Surat Undangan', 'Undangan kegiatan atau rapat warga',
                "UNDANGAN\nNomor: {{nomor}}\n\nKepada Yth. Bapak/Ibu Warga RT 03\n\nDiharapkan kehadiran Bapak/Ibu pada kegiatan: {{keperluan}}.\n\nDemikian undangan ini disampaikan, atas perhatian dan kehadirannya diucapkan terima kasih.\n\n{{ttd}}"],
            ['SK-NIKAH', 'Surat Pengantar Nikah', 'Pengantar administrasi pernikahan',
                "SURAT PENGANTAR NIKAH\nNomor: {{nomor}}\n\nMenerangkan bahwa {{nama}} (NIK {{nik}}), beralamat di {{alamat}}, adalah warga RT 03 / RW 20 dan bermaksud melangsungkan pernikahan.\n\nDemikian surat pengantar ini dibuat untuk dipergunakan sebagaimana mestinya.\n\n{{ttd}}"],
            ['SK-USAHA', 'Surat Keterangan Usaha', 'Keterangan usaha milik warga',
                "SURAT KETERANGAN USAHA\nNomor: {{nomor}}\n\nMenerangkan bahwa {{nama}} (NIK {{nik}}), beralamat di {{alamat}}, memiliki usaha: {{keperluan}}.\n\nDemikian surat keterangan ini dibuat dengan sebenarnya.\n\n{{ttd}}"],
        ];
        $out = [];
        foreach ($rows as [$kode, $nama, $desk, $isi]) {
            $out[$kode] = TemplateSurat::firstOrCreate(['kode' => $kode], ['nama' => $nama, 'deskripsi' => $desk, 'isi' => $isi])->id;
        }

        return $out;
    }
}
