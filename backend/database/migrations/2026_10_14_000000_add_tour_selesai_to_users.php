<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kapan pengguna menyelesaikan/melewati panduan awal. NULL = belum pernah → panduan tampil saat login.
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('tour_selesai_at')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('tour_selesai_at'));
    }
};
