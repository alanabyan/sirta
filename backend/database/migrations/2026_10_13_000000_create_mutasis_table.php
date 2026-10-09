<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bayi yang baru lahir sering belum memiliki NIK; NIK diisi menyusul.
        Schema::table('wargas', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->change();
        });

        Schema::create('mutasis', function (Blueprint $table) {
            $table->id();
            // Nama disalin agar catatan tetap utuh walau data warganya kelak dihapus.
            $table->foreignId('warga_id')->nullable()->constrained('wargas')->nullOnDelete();
            $table->string('nama_warga');
            $table->string('jenis'); // lahir | masuk | keluar | meninggal
            $table->date('tanggal');
            $table->string('keterangan')->nullable(); // asal / tujuan / catatan
            $table->string('status_sebelum')->nullable(); // untuk memulihkan saat dibatalkan
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('alasan_batal')->nullable();
            $table->timestamps();
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasis');
    }
};
