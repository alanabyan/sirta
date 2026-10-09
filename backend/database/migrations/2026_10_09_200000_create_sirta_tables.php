<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keluargas', function (Blueprint $table) {
            $table->id();
            $table->string('no_kk', 16)->unique();
            $table->string('kepala_keluarga');
            $table->string('alamat');
            $table->string('status_rumah')->default('Milik sendiri');
            $table->timestamps();
        });

        Schema::create('wargas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keluarga_id')->nullable()->constrained('keluargas')->nullOnDelete();
            $table->string('nik', 16)->unique();
            $table->string('nama');
            $table->string('jenis_kelamin');
            $table->date('tanggal_lahir');
            $table->string('pekerjaan')->nullable();
            $table->string('hubungan_keluarga')->nullable();
            $table->string('telepon')->nullable();
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });

        Schema::create('pengurus', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('jabatan');
            $table->string('periode')->default('2025–2028');
            $table->string('kontak')->nullable();
            $table->timestamps();
        });

        Schema::create('template_surats', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('deskripsi')->nullable();
            $table->text('isi');
            $table->timestamps();
        });

        Schema::create('surat_masuks', function (Blueprint $table) {
            $table->id();
            $table->string('nomor');
            $table->date('tanggal');
            $table->string('pengirim');
            $table->string('perihal');
            $table->string('status')->default('Baru');
            $table->timestamps();
        });

        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->date('tanggal');
            $table->string('tujuan');
            $table->string('perihal');
            $table->text('isi')->nullable();
            $table->string('status')->default('Draft');
            $table->foreignId('template_surat_id')->nullable()->constrained('template_surats')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('arsips', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kategori');
            $table->unsignedBigInteger('ukuran')->default(0); // bytes
            $table->string('path')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pengajuans', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('warga_id')->constrained('wargas')->cascadeOnDelete();
            $table->string('layanan');
            $table->text('keperluan')->nullable();
            $table->string('status')->default('Menunggu Verifikasi');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('pengajuan_riwayats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('deskripsi');
            $table->string('ikon')->default('info');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['aktivitas', 'pengajuan_riwayats', 'pengajuans', 'arsips', 'surat_keluars', 'surat_masuks', 'template_surats', 'pengurus', 'wargas', 'keluargas'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
