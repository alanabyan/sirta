<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use Illuminate\Database\Seeder;

/**
 * Data awal untuk server produksi — TANPA akun, warga, atau surat contoh.
 *   php artisan db:seed --class=ProduksiSeeder --force
 * Lalu buat admin pertama: php artisan sirta:admin
 */
class ProduksiSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TemplateSuratSeeder::class);

        // Isi awal; Ketua RT mengubahnya lewat menu "Identitas & Tanda Tangan".
        Pengaturan::saatIni();
    }
}
