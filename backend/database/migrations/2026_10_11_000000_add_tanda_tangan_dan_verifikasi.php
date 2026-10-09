<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Identitas RT & penandatangan (satu baris saja).
        Schema::create('pengaturans', function (Blueprint $table) {
            $table->id();
            $table->text('kop');
            $table->string('kota')->default('Bekasi');
            $table->string('penandatangan_nama');
            $table->string('penandatangan_jabatan');
            $table->string('tanda_tangan_path')->nullable(); // disk privat
            $table->string('stempel_path')->nullable();      // disk privat
            $table->timestamps();
        });

        // Surat yang diterbitkan mendapat kode untuk diperiksa keasliannya lewat QR.
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->string('kode_verifikasi', 16)->nullable()->unique()->after('status');
            $table->timestamp('diterbitkan_at')->nullable()->after('kode_verifikasi');
            $table->foreignId('diterbitkan_oleh')->nullable()->after('diterbitkan_at')->constrained('users')->nullOnDelete();
        });

        // Surat yang sudah terbit sebelum fitur ini ikut diberi kode.
        DB::table('surat_keluars')->where('status', 'Diterbitkan')->whereNull('kode_verifikasi')->orderBy('id')->each(function ($s) {
            DB::table('surat_keluars')->where('id', $s->id)->update([
                'kode_verifikasi' => Str::upper(Str::random(10)),
                'diterbitkan_at' => $s->created_at,
            ]);
        });

        // Kop & tanda tangan kini dibuat otomatis dari pengaturan: bersihkan dari template lama.
        DB::table('template_surats')->orderBy('id')->each(function ($t) {
            $isi = preg_replace('/^RUKUN TETANGGA[^\n]*\n[^\n]*\n[^\n]*\n+/u', '', $t->isi);
            $isi = preg_replace('/\n*[^\n]*, \{\{tanggal\}\}.*$/su', "\n\n{{ttd}}", $isi);
            if (! str_contains($isi, '{{ttd}}')) {
                $isi = rtrim($isi)."\n\n{{ttd}}";
            }
            DB::table('template_surats')->where('id', $t->id)->update(['isi' => $isi]);
        });
    }

    public function down(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diterbitkan_oleh');
            $table->dropColumn(['kode_verifikasi', 'diterbitkan_at']);
        });
        Schema::dropIfExists('pengaturans');
    }
};
