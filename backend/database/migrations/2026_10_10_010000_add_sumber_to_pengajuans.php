<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('sumber')->default('pengurus')->after('status'); // pengurus | mandiri
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', fn (Blueprint $t) => $t->dropColumn('sumber'));
    }
};
