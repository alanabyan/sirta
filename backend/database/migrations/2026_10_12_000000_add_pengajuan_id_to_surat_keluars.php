<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            // Satu permohonan paling banyak punya satu surat; hapus surat → permohonan bebas dibuatkan lagi.
            $table->foreignId('pengajuan_id')->nullable()->unique()->after('template_surat_id')->constrained('pengajuans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('surat_keluars', fn (Blueprint $t) => $t->dropConstrainedForeignId('pengajuan_id'));
    }
};
