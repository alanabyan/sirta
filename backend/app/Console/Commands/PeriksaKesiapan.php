<?php

namespace App\Console\Commands;

use App\Models\Pengaturan;
use App\Models\TemplateSurat;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PeriksaKesiapan extends Command
{
    protected $signature = 'sirta:periksa';

    protected $description = 'Memeriksa apakah server siap dipakai sungguhan (pengaturan, database, izin folder, akun bawaan)';

    private int $gagal = 0;

    private int $peringatan = 0;

    private function ok(string $t): void
    {
        $this->line("  <info>✔</info> {$t}");
    }

    private function salah(string $t, string $saran): void
    {
        $this->gagal++;
        $this->line("  <error> ✘ </error> {$t}");
        $this->line("      → {$saran}");
    }

    private function awas(string $t, string $saran): void
    {
        $this->peringatan++;
        $this->line("  <comment>!</comment> {$t}");
        $this->line("      → {$saran}");
    }

    public function handle(): int
    {
        $this->newLine();
        $this->info('Pemeriksaan kesiapan SIRTA');
        $this->newLine();

        $this->line('<options=bold>Lingkungan</>');
        version_compare(PHP_VERSION, '8.3.0', '>=') ? $this->ok('PHP '.PHP_VERSION) : $this->salah('PHP '.PHP_VERSION.' terlalu lama', 'Laravel 13 butuh PHP 8.3+. Ganti di hPanel → PHP Configuration.');
        foreach (['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'ctype', 'xml', 'tokenizer', 'zip'] as $ext) {
            extension_loaded($ext) ? null : ($ext === 'zip' ? $this->awas("Ekstensi {$ext} tidak aktif", 'Opsional, tapi sebaiknya diaktifkan.') : $this->salah("Ekstensi PHP {$ext} tidak aktif", 'Aktifkan di hPanel → PHP Configuration → Extensions.'));
        }
        app()->environment('production') ? $this->ok('APP_ENV=production') : $this->salah('APP_ENV='.app()->environment(), 'Ubah APP_ENV=production di file .env.');
        config('app.debug') ? $this->salah('APP_DEBUG=true — pesan galat membocorkan isi kode & konfigurasi', 'Ubah APP_DEBUG=false di file .env.') : $this->ok('APP_DEBUG=false');
        config('app.key') ? $this->ok('APP_KEY terisi') : $this->salah('APP_KEY kosong', 'Jalankan: php artisan key:generate --force');
        str_starts_with((string) config('app.url'), 'https://') ? $this->ok('APP_URL memakai HTTPS ('.config('app.url').')') : $this->awas('APP_URL='.config('app.url').' bukan HTTPS', 'Aktifkan SSL di hPanel dan ubah APP_URL=https://domain-anda.');
        $unggah = ini_parse_quantity((string) ini_get('upload_max_filesize'));
        $kirim = ini_parse_quantity((string) ini_get('post_max_size'));
        ($unggah >= 10 * 1024 * 1024 && $kirim >= 10 * 1024 * 1024)
            ? $this->ok('Batas unggah PHP cukup (berkas '.ini_get('upload_max_filesize').', permintaan '.ini_get('post_max_size').')')
            : $this->awas('Batas unggah PHP kecil (berkas '.ini_get('upload_max_filesize').', permintaan '.ini_get('post_max_size').')', 'Arsip hingga 10 MB akan gagal. Naikkan upload_max_filesize & post_max_size di hPanel → PHP Configuration (atau lewat public/.user.ini).');
        in_array(config('database.default'), ['mysql', 'mariadb'], true) ? $this->ok('Database: '.config('database.default')) : $this->awas('Database: '.config('database.default'), 'Produksi sebaiknya memakai MySQL.');

        $this->newLine();
        $this->line('<options=bold>Database</>');
        try {
            DB::connection()->getPdo();
            $this->ok('Terhubung ke database “'.config('database.connections.'.config('database.default').'.database').'”');
            $belum = collect(DB::select('select migration from migrations'))->pluck('migration');
            $berkas = collect(glob(database_path('migrations/*.php')))->map(fn ($f) => basename($f, '.php'));
            $tertunda = $berkas->diff($belum);
            $tertunda->isEmpty() ? $this->ok('Semua migrasi sudah dijalankan ('.$berkas->count().')') : $this->salah($tertunda->count().' migrasi belum dijalankan', 'Jalankan: php artisan migrate --force');
        } catch (\Throwable $e) {
            $this->salah('Tidak dapat terhubung ke database: '.$e->getMessage(), 'Periksa DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD di .env.');
            $this->newLine();

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('<options=bold>Folder & izin tulis</>');
        foreach ([storage_path('app'), storage_path('framework'), storage_path('logs'), base_path('bootstrap/cache')] as $d) {
            is_writable($d) ? $this->ok('Dapat ditulis: '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $d)) : $this->salah('Tidak dapat ditulis: '.$d, 'Atur izin folder menjadi 775 (chmod -R 775 storage bootstrap/cache).');
        }
        is_dir(storage_path('app/private')) ? $this->ok('Folder berkas privat ada (lampiran & arsip)') : $this->awas('storage/app/private belum ada', 'Akan dibuat otomatis saat berkas pertama diunggah; pastikan storage/app dapat ditulis.');

        $this->newLine();
        $this->line('<options=bold>Data & akun</>');
        $admin = User::where('role', 'administrator')->where('is_active', true)->count();
        $admin > 0 ? $this->ok("Ada {$admin} akun Administrator aktif") : $this->salah('Belum ada akun Administrator', 'Jalankan: php artisan sirta:admin');
        TemplateSurat::count() > 0 ? $this->ok('Template surat tersedia ('.TemplateSurat::count().')') : $this->awas('Belum ada template surat', 'Jalankan: php artisan db:seed --class=ProduksiSeeder --force');
        Pengaturan::count() > 0 ? $this->ok('Identitas RT (kop & penandatangan) sudah ada') : $this->awas('Identitas RT belum diisi', 'Jalankan ProduksiSeeder, lalu isi menu Identitas & Tanda Tangan.');

        // Akun yang masih memakai kata sandi bawaan "password" adalah celah paling umum.
        $lemah = User::all()->filter(fn ($u) => Hash::check('password', $u->password))->pluck('username');
        $lemah->isEmpty() ? $this->ok('Tidak ada akun dengan kata sandi bawaan "password"') : $this->salah('Akun masih memakai kata sandi bawaan "password": '.$lemah->implode(', '), 'Ganti kata sandinya (php artisan sirta:admin --username=NAMA --reset) atau hapus akunnya dari menu Pengguna Sistem.');

        $this->newLine();
        if ($this->gagal) {
            $this->error("Belum siap: {$this->gagal} masalah perlu diperbaiki".($this->peringatan ? ", {$this->peringatan} peringatan." : '.'));

            return self::FAILURE;
        }
        $this->info('Siap dipakai.'.($this->peringatan ? " ({$this->peringatan} peringatan, periksa di atas)" : ''));

        return self::SUCCESS;
    }
}
