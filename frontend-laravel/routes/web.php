<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Operator\OperatorDashboardController;
use App\Http\Controllers\Operator\MasterData\PendapatanController;
use App\Http\Controllers\Operator\MasterData\RayonController;
use App\Http\Controllers\Operator\MasterData\JuruParkirController;
use App\Http\Controllers\Operator\MasterData\HariLiburController;
use App\Http\Controllers\Operator\OperatorPrediksiController;
use App\Http\Controllers\Operator\OperatorOptimasiController;
use App\Http\Controllers\Operator\OperatorLaporanController;

use App\Http\Controllers\KepalaUpt\KepalaUptDashboardController;
use App\Http\Controllers\KepalaUpt\KepalaUptPrediksiController;
use App\Http\Controllers\KepalaUpt\KepalaUptOptimasiController;
use App\Http\Controllers\KepalaUpt\KepalaUptLaporanController;

use App\Http\Controllers\KepalaDishub\KepalaDishubDashboardController;
use App\Http\Controllers\KepalaDishub\KepalaDishubPrediksiController;
use App\Http\Controllers\KepalaDishub\KepalaDishubOptimasiController;
use App\Http\Controllers\KepalaDishub\KepalaDishubLaporanController;

// Rute Default / Redirect ke Halaman Login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rute Guest (Hanya untuk pengguna yang BELUM login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Rute Logout (Bisa diakses jika sudah login)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ==========================================
// 1. RUTE KHUSUS OPERATOR UPT PARKIR
// ==========================================
Route::prefix('operator')->middleware(['auth', 'role:operator'])->name('operator.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [OperatorDashboardController::class, 'index'])->name('dashboard');

    // Master Data
    Route::get('/master-data/pendapatan/template', [PendapatanController::class, 'downloadTemplate'])->name('pendapatan.template');
    Route::resource('/master-data/pendapatan', PendapatanController::class);
    Route::post('/master-data/pendapatan/import', [PendapatanController::class, 'import'])->name('pendapatan.import');

    Route::resource('/master-data/rayon', RayonController::class);
    Route::resource('/master-data/juru-parkir', JuruParkirController::class);
    Route::post('/master-data/hari-libur/generate', [HariLiburController::class, 'generate'])->name('hari-libur.generate');
    Route::resource('/master-data/hari-libur', HariLiburController::class);

    // Modul Prediksi SVR
    Route::get('/prediksi', [OperatorPrediksiController::class, 'index'])->name('prediksi.index');
    Route::post('/prediksi/jalankan-svr', [OperatorPrediksiController::class, 'runSvr'])->name('prediksi.run-svr');

    // Modul Optimasi Parameter
    Route::get('/optimasi', [OperatorOptimasiController::class, 'index'])->name('optimasi.index');
    Route::post('/optimasi/grid-search', [OperatorOptimasiController::class, 'runGridSearch'])->name('optimasi.grid-search');
    Route::post('/optimasi/gwo', [OperatorOptimasiController::class, 'runGwo'])->name('optimasi.gwo');

    // Modul Laporan
    Route::get('/laporan', [OperatorLaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export-pdf', [OperatorLaporanController::class, 'exportPdf'])->name('laporan.export-pdf');
    Route::get('/laporan/export-excel', [OperatorLaporanController::class, 'exportExcel'])->name('laporan.export-excel');
});

// ==========================================
// 2. RUTE KHUSUS KEPALA UPT PARKIR
// ==========================================
Route::prefix('kepala-upt')->middleware(['auth', 'role:kepala_upt'])->name('kepala-upt.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [KepalaUptDashboardController::class, 'index'])->name('dashboard');

    // Pemantauan Model Prediksi & Hasil Optimasi
    Route::get('/prediksi', [KepalaUptPrediksiController::class, 'index'])->name('prediksi.index');
    Route::get('/optimasi', [KepalaUptOptimasiController::class, 'index'])->name('optimasi.index');

    // Laporan Prediksi Pendapatan Retribusi (PDF)
    Route::get('/laporan', [KepalaUptLaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export-pdf', [KepalaUptLaporanController::class, 'exportPdf'])->name('laporan.export-pdf');
});

// ==========================================
// 3. RUTE KHUSUS KEPALA DISHUB
// ==========================================
Route::prefix('kepala-dishub')->middleware(['auth', 'role:kepala_dishub'])->name('kepala-dishub.')->group(function () {
    // Dashboard Eksekutif
    Route::get('/dashboard', [KepalaDishubDashboardController::class, 'index'])->name('dashboard');

    // Pemantauan Tren Prediksi & Hasil Optimasi
    Route::get('/prediksi', [KepalaDishubPrediksiController::class, 'index'])->name('prediksi.index');
    Route::get('/optimasi', [KepalaDishubOptimasiController::class, 'index'])->name('optimasi.index');

    // Laporan Prediksi Pendapatan Retribusi (PDF)
    Route::get('/laporan', [KepalaDishubLaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export-pdf', [KepalaDishubLaporanController::class, 'exportPdf'])->name('laporan.export-pdf');
});
