<?php

use App\Http\Controllers\Api\AjukanController;
use App\Http\Controllers\Api\ArsipController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CekSuratController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\KeluargaController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\MutasiController;
use App\Http\Controllers\Api\PengajuanController;
use App\Http\Controllers\Api\PengaturanController;
use App\Http\Controllers\Api\PengurusController;
use App\Http\Controllers\Api\SuratKeluarController;
use App\Http\Controllers\Api\SuratMasukController;
use App\Http\Controllers\Api\TemplateSuratController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WargaController;
use Illuminate\Support\Facades\Route;

// Publik
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/ajukan', AjukanController::class)->middleware('throttle:5,1');
Route::get('/cek-surat/{kode}', CekSuratController::class)->middleware('throttle:20,1');
Route::post('/tracking', TrackingController::class)->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/password', [AuthController::class, 'updatePassword']);

    // Semua peran dapat melihat data.
    Route::get('/dashboard', DashboardController::class);
    Route::get('/antrean', [DashboardController::class, 'antrean']);
    Route::get('/pengaturan', [PengaturanController::class, 'show']);
    Route::get('/warga/ringkasan', [WargaController::class, 'ringkasan']);
    Route::get('/arsip/ringkasan', [ArsipController::class, 'ringkasan']);
    Route::get('/arsip/{id}/unduh', [ArsipController::class, 'unduh'])->whereNumber('id');
    Route::get('/pengajuan/{id}', [PengajuanController::class, 'show'])->whereNumber('id');

    foreach ([
        'warga' => WargaController::class,
        'keluarga' => KeluargaController::class,
        'pengurus' => PengurusController::class,
        'surat-masuk' => SuratMasukController::class,
        'surat-keluar' => SuratKeluarController::class,
        'arsip' => ArsipController::class,
        'template-surat' => TemplateSuratController::class,
        'pengajuan' => PengajuanController::class,
    ] as $uri => $controller) {
        Route::get($uri, [$controller, 'index']);
        if ($uri !== 'pengajuan') {
            Route::get("$uri/{id}", [$controller, 'show'])->whereNumber('id');
        }
    }

    // Mengubah data: bendahara hanya dapat melihat.
    Route::middleware('role:administrator,ketua_rt,sekretaris,operator')->group(function () {
        Route::get('/pengaturan/gambar/{jenis}', [PengaturanController::class, 'gambar']);
        Route::get('warga/{id}/nik', [WargaController::class, 'nik'])->whereNumber('id');
        Route::post('warga', [WargaController::class, 'store']);
        Route::put('warga/{id}', [WargaController::class, 'update']);
        Route::post('keluarga', [KeluargaController::class, 'store']);
        Route::put('keluarga/{id}', [KeluargaController::class, 'update']);
        Route::post('surat-masuk', [SuratMasukController::class, 'store']);
        Route::put('surat-masuk/{id}', [SuratMasukController::class, 'update']);
        Route::post('surat-keluar', [SuratKeluarController::class, 'store']);
        Route::put('surat-keluar/{id}', [SuratKeluarController::class, 'update']);
        Route::post('arsip', [ArsipController::class, 'store']);
        Route::put('arsip/{id}', [ArsipController::class, 'update']);
        Route::get('pengajuan/{id}/lampiran/{lampiranId}', [PengajuanController::class, 'lampiran'])->whereNumber(['id', 'lampiranId']);
        Route::post('pengajuan', [PengajuanController::class, 'store']);
        Route::put('pengajuan/{id}', [PengajuanController::class, 'update']);
    });

    // Verifikasi, penghapusan, struktur pengurus, & template: pengambil keputusan.
    Route::middleware('role:administrator,ketua_rt,sekretaris')->group(function () {
        Route::get('mutasi', [MutasiController::class, 'index']);
        Route::get('mutasi/ringkasan', [MutasiController::class, 'ringkasan']);
        Route::post('mutasi', [MutasiController::class, 'store']);
        Route::post('mutasi/{id}/batal', [MutasiController::class, 'batal'])->whereNumber('id');
        Route::get('laporan/warga', [LaporanController::class, 'warga']);
        Route::get('laporan/keluarga', [LaporanController::class, 'keluarga']);
        Route::get('laporan/bulanan', [LaporanController::class, 'bulanan']);
        Route::patch('pengajuan/{id}/status', [PengajuanController::class, 'ubahStatus']);
        foreach (['warga' => WargaController::class, 'keluarga' => KeluargaController::class,
            'surat-masuk' => SuratMasukController::class, 'surat-keluar' => SuratKeluarController::class,
            'arsip' => ArsipController::class, 'pengajuan' => PengajuanController::class] as $uri => $c) {
            Route::delete("$uri/{id}", [$c, 'destroy']);
        }
        Route::post('pengurus', [PengurusController::class, 'store']);
        Route::put('pengurus/{id}', [PengurusController::class, 'update']);
        Route::delete('pengurus/{id}', [PengurusController::class, 'destroy']);
        Route::post('template-surat', [TemplateSuratController::class, 'store']);
        Route::put('template-surat/{id}', [TemplateSuratController::class, 'update']);
        Route::delete('template-surat/{id}', [TemplateSuratController::class, 'destroy']);
    });

    // Identitas RT, tanda tangan & stempel: Ketua RT dan Administrator.
    Route::post('/pengaturan', [PengaturanController::class, 'update'])->middleware('role:administrator,ketua_rt');

    // Manajemen pengguna: administrator saja.
    Route::middleware('role:administrator')->group(function () {
        Route::get('log-nik', [WargaController::class, 'logNik']);
        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::put('users/{id}', [UserController::class, 'update']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);
    });
});
