<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_lampirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained()->cascadeOnDelete();
            $table->string('nama');          // nama berkas asli (untuk ditampilkan)
            $table->string('path');          // lokasi di disk privat
            $table->string('mime', 100);
            $table->unsignedBigInteger('ukuran');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_lampirans');
    }
};
