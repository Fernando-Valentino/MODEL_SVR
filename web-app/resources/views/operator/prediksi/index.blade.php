@extends('layouts.app')

@section('title', 'Kelola Model Prediksi')
@section('subtitle', 'Generate prediksi pendapatan retribusi parkir menggunakan model Support Vector Regression (SVR) standar.')

@section('content')
<div class="container-fluid p-0">
    
    <!-- Custom CSS Styles -->
    <style>
        /* Stepper Style */
        .stepper-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        .stepper-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        .stepper-item .step-number {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--background);
            border: 2px solid var(--border);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }
        .stepper-item .step-title {
            font-size: 11.5px;
            font-weight: 500;
            color: var(--text-secondary);
            text-align: center;
        }
        .stepper-line {
            flex: 1;
            height: 2px;
            background-color: var(--border);
            margin-bottom: 24px;
            transition: background-color 0.3s ease;
        }
        .stepper-line.completed {
            background-color: var(--success) !important;
        }
        .stepper-item.active .step-number {
            background-color: var(--primary-blue-light);
            border-color: var(--primary-blue);
            color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0, 91, 170, 0.15);
        }
        .stepper-item.active .step-title {
            color: var(--primary-blue);
            font-weight: 600;
        }
        .stepper-item.completed .step-number {
            background-color: var(--success);
            border-color: var(--success);
            color: #ffffff;
        }
        .stepper-item.completed .step-title {
            color: var(--success);
            font-weight: 600;
        }

        /* Card and Table Tweaks */
        .text-primary-custom {
            color: var(--primary-blue);
        }
        .bg-success-subtle {
            background-color: rgba(22, 163, 74, 0.1) !important;
        }
        .bg-danger-subtle {
            background-color: rgba(220, 38, 38, 0.1) !important;
        }
        .bg-primary-subtle {
            background-color: rgba(0, 91, 170, 0.1) !important;
        }
        .text-sm {
            font-size: 13px !important;
        }

        /* Metric Cards Styling to avoid wrapping */
        .metric-card-custom {
            background-color: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px 14px !important;
            box-shadow: 0 1px 3px rgba(0, 91, 170, 0.03);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
            transition: all 0.2s ease-in-out;
        }
        .metric-card-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 91, 170, 0.08);
            border-color: rgba(0, 91, 170, 0.3);
        }
        .metric-label-custom {
            font-size: 10.5px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
        }
        .metric-value-custom {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
            white-space: nowrap !important;
            display: block;
        }
        .metric-value-custom.text-success {
            color: var(--success) !important;
        }

        /* Step checklist styling */
        .progress-step {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 12.5px;
            color: var(--text-secondary);
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        .progress-step.active {
            background-color: var(--primary-blue-light);
            color: var(--primary-blue);
            font-weight: 600;
        }
        .progress-step.active .step-label {
            color: var(--primary-blue) !important;
        }
        .progress-step.success-step {
            background-color: rgba(22, 163, 74, 0.05);
            color: var(--success);
        }
        .progress-step.success-step .step-label {
            color: var(--success) !important;
        }
        .progress-step.failed-step {
            background-color: rgba(220, 38, 38, 0.05);
            color: var(--danger);
        }
        .progress-step.failed-step .step-label {
            color: var(--danger) !important;
        }
        
        /* Table Cell Text Wrapping Fix */
        .table-custom-nowrap th, 
        .table-custom-nowrap td {
            white-space: nowrap !important;
            vertical-align: middle;
        }

        /* Custom Nav Pills for Pipeline Steps */
        #v-pills-tab .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 14px !important;
            border-radius: 10px !important;
            margin-bottom: 8px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 12.5px !important;
            font-weight: 600;
            text-align: left;
            transition: all 0.2s ease-in-out;
            line-height: 1.3;
        }
        #v-pills-tab .nav-link .tab-step-number {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 11px;
            margin-right: 10px;
            flex-shrink: 0;
            border: 2px solid #cbd5e1;
            background-color: #ffffff;
            color: #64748b;
            transition: all 0.2s ease;
        }
        #v-pills-tab .nav-link:hover {
            background-color: var(--primary-blue-light);
            color: var(--primary-blue);
            border-color: rgba(0, 91, 170, 0.2);
        }
        #v-pills-tab .nav-link:hover .tab-step-number {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }
        #v-pills-tab .nav-link.active {
            background-color: var(--primary-blue) !important;
            color: #ffffff !important;
            border-color: var(--primary-blue) !important;
            box-shadow: 0 4px 10px rgba(0, 91, 170, 0.15);
        }
        #v-pills-tab .nav-link.active .tab-step-number {
            background-color: #ffffff;
            border-color: #ffffff;
            color: var(--primary-blue);
        }
        
        /* Compact table preview styling */
        .table-preview-custom {
            font-size: 11.5px !important;
        }
        .table-preview-custom th,
        .table-preview-custom td {
            padding: 6px 8px !important;
        }
        
        .bg-light-subtle {
            background-color: #f8fafc !important;
        }
    </style>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- 1. Stepper Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="stepper-wrapper">
                <div class="stepper-item active" id="stepper-item-1" style="cursor: pointer;" onclick="goToStep(1)">
                    <div class="step-number">1</div>
                    <div class="step-title">Validasi Dataset</div>
                </div>
                <div class="stepper-line" id="stepper-line-1"></div>
                <div class="stepper-item" id="stepper-item-2" style="cursor: pointer;" onclick="goToStep(2)">
                    <div class="step-number">2</div>
                    <div class="step-title">Konfigurasi SVR</div>
                </div>
                <div class="stepper-line" id="stepper-line-2"></div>
                <div class="stepper-item" id="stepper-item-3" style="cursor: pointer;" onclick="goToStep(3)">
                    <div class="step-number">3</div>
                    <div class="step-title">Generate Prediksi</div>
                </div>
                <div class="stepper-line" id="stepper-line-3"></div>
                <div class="stepper-item" id="stepper-item-4" style="cursor: pointer;" onclick="goToStep(4)">
                    <div class="step-number">4</div>
                    <div class="step-title">Hasil Evaluasi</div>
                </div>
            </div>
        </div>
    </div>

    <!-- STEP 1 CONTENT -->
    <div id="step-content-1" class="step-content-section d-none">
        <!-- 2. Dataset Ringkasan & Validasi Section -->
        <div class="row g-4 mb-4">
            <!-- Ringkasan Dataset -->
            <div class="col-md-6">
                <div class="card h-100 mb-0">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-info-circle-fill me-2 text-primary-custom"></i>Ringkasan Dataset</h5>
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle mb-0 text-sm">
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold text-secondary" style="width: 50%;">Total Data Pendapatan</td>
                                        <td class="text-end fw-bold text-dark">{{ number_format($totalPendapatan, 0, ',', '.') }} Baris</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-secondary">Periode Data Awal</td>
                                        <td class="text-end fw-bold text-dark">{{ $periodeAwalFormatted }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-secondary">Periode Data Akhir</td>
                                        <td class="text-end fw-bold text-dark">{{ $periodeAkhirFormatted }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-secondary">Jumlah Rayon</td>
                                        <td class="text-end fw-bold text-dark">{{ $jumlahRayon }} Rayon</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-secondary">Data Libur & Weekend</td>
                                        <td class="text-end">
                                            <span class="badge badge-holiday me-1">{{ $jumlahHariLibur }} Libur</span>
                                            <span class="badge badge-weekend">{{ $jumlahWeekend }} Weekend</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-secondary">Status Dataset</td>
                                        <td class="text-end">
                                            @if($datasetReady)
                                                <span class="badge badge-active"><i class="bi bi-patch-check-fill me-1"></i>Siap Diproses</span>
                                            @else
                                                <span class="badge badge-inactive"><i class="bi bi-exclamation-triangle-fill me-1"></i>Belum Siap</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validasi Kelengkapan Dataset -->
            <div class="col-md-6">
                <div class="card h-100 mb-0">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title"><i class="bi bi-shield-check-fill me-2 text-primary-custom"></i>Validasi Kelengkapan Dataset</h5>
                            <ul class="list-group list-group-flush text-sm">
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-light py-2">
                                    <span><i class="bi {{ $hasPendapatan ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} me-2"></i> Data pendapatan</span>
                                    <span class="badge {{ $hasPendapatan ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border-0">{{ $hasPendapatan ? 'Lengkap' : 'Tidak Ada' }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-light py-2">
                                    <span><i class="bi {{ $hasRayon ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} me-2"></i> Data rayon</span>
                                    <span class="badge {{ $hasRayon ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border-0">{{ $hasRayon ? 'Lengkap' : 'Tidak Ada' }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-light py-2">
                                    <span><i class="bi {{ $hasJuruParkir ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} me-2"></i> Data juru parkir</span>
                                    <span class="badge {{ $hasJuruParkir ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border-0">{{ $hasJuruParkir ? 'Lengkap' : 'Tidak Ada' }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-light py-2">
                                    <span><i class="bi {{ $hasHariLibur ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} me-2"></i> Data hari libur dan weekend</span>
                                    <span class="badge {{ $hasHariLibur ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border-0">{{ $hasHariLibur ? 'Lengkap' : 'Tidak Ada' }}</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="mt-3 p-2 rounded {{ $datasetReady ? 'alert alert-success' : 'alert alert-danger' }} mb-0 py-2 px-3 small border-0 d-flex align-items-center">
                            <i class="bi {{ $datasetReady ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger' }} me-2 fs-5"></i>
                            <span class="text-dark">
                                @if($datasetReady)
                                    <strong>Dataset siap digunakan</strong> untuk SVR standar.
                                @else
                                    <strong>Dataset belum lengkap.</strong> Silakan lengkapi data pada menu Master Data Retribusi terlebih dahulu.
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Footer for Step 1 -->
        <div class="d-flex justify-content-end mb-4">
            <button class="btn btn-dark px-4 py-2.5 rounded-3 fw-semibold shadow-sm" onclick="goToStep(2)" {{ !$datasetReady ? 'disabled' : '' }}>
                Lanjut ke Konfigurasi SVR <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>

    <!-- STEP 2 CONTENT -->
    <div id="step-content-2" class="step-content-section d-none">
        <div class="row g-4 mb-4">
            <!-- Konfigurasi SVR Standar -->
            <div class="col-12">
                <div class="card mb-0 bg-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-sliders me-2 text-primary-custom"></i>Konfigurasi SVR Standar</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 12.5px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Nilai</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold">Kernel</td>
                                        <td><code class="text-primary fw-bold bg-light px-2 py-1 rounded">RBF</code></td>
                                        <td>Kernel Radial Basis Function untuk menangani pola non-linear</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">C</td>
                                        <td><code class="text-primary fw-bold bg-light px-2 py-1 rounded">1.0</code></td>
                                        <td>Penalti terhadap kesalahan prediksi (regularisasi)</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Epsilon (&epsilon;)</td>
                                        <td><code class="text-primary fw-bold bg-light px-2 py-1 rounded">0.1</code></td>
                                        <td>Batas lebar tabung toleransi kesalahan prediksi</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Gamma (&gamma;)</td>
                                        <td><code class="text-primary fw-bold bg-light px-2 py-1 rounded">scale</code></td>
                                        <td>Parameter koefisien kernel default Scikit-learn</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 p-2 bg-light rounded text-muted small border-start border-4 border-warning px-3 py-2">
                            <i class="bi bi-info-circle-fill me-1 text-warning"></i>
                            Konfigurasi parameter ini digunakan sebagai <strong>model awal / default</strong> sebelum dilakukan optimasi hyperparameter pada menu <strong>Optimasi Parameter</strong>.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Footer for Step 2 -->
        <div class="d-flex justify-content-between mb-4">
            <button class="btn btn-outline-secondary px-4 py-2.5 rounded-3 fw-semibold" onclick="goToStep(1)">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Validasi Dataset
            </button>
            <button class="btn btn-dark px-4 py-2.5 rounded-3 fw-semibold shadow-sm" onclick="goToStep(3)">
                Lanjut ke Generate Prediksi <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>

    <!-- STEP 3 CONTENT -->
    <div id="step-content-3" class="step-content-section d-none">
        <div class="row g-4 mb-4">
            <!-- Panel Eksekusi (7 Langkah) -->
            <div class="col-md-5">
                <div class="card h-100 bg-white">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title text-dark"><i class="bi bi-cpu-fill me-2 text-primary-custom"></i>Status & Eksekusi Model</h5>
                            <p class="text-muted small mb-3">Sistem akan memanggil Python API untuk melakukan pemrosesan data, rekayasa fitur, dan pelatihan model SVR secara berurutan.</p>
                            
                            <div class="progress-steps-list">
                                @php
                                    $isExecuted = isset($lastRun) && $lastRun->status == 'success';
                                    $stepClass = $isExecuted ? 'success-step' : '';
                                    $iconHtml = $isExecuted ? '<i class="bi bi-check-circle-fill text-success" style="font-size: 14px;"></i>' : '<i class="bi bi-circle"></i>';
                                    $iconClass = $isExecuted ? 'step-icon me-2' : 'step-icon me-2 text-muted';
                                @endphp
                                <div class="progress-step {{ $stepClass }}" id="step-1">
                                    <span class="{{ $iconClass }}">{!! $iconHtml !!}</span>
                                    <span class="step-label">1. Pembersihan Data (Data Cleaning)</span>
                                </div>
                                <div class="progress-step {{ $stepClass }}" id="step-2">
                                    <span class="{{ $iconClass }}">{!! $iconHtml !!}</span>
                                    <span class="step-label">2. Rekayasa Fitur (Feature Engineering)</span>
                                </div>
                                <div class="progress-step {{ $stepClass }}" id="step-3">
                                    <span class="{{ $iconClass }}">{!! $iconHtml !!}</span>
                                    <span class="step-label">3. Transformasi Data</span>
                                </div>
                                <div class="progress-step {{ $stepClass }}" id="step-4">
                                    <span class="{{ $iconClass }}">{!! $iconHtml !!}</span>
                                    <span class="step-label">4. Normalisasi Data</span>
                                </div>
                                <div class="progress-step {{ $stepClass }}" id="step-5">
                                    <span class="{{ $iconClass }}">{!! $iconHtml !!}</span>
                                    <span class="step-label">5. Pembagian Data (Split Data 80:20)</span>
                                </div>
                                <div class="progress-step {{ $stepClass }}" id="step-6">
                                    <span class="{{ $iconClass }}">{!! $iconHtml !!}</span>
                                    <span class="step-label">6. Pelatihan Model SVR</span>
                                </div>
                                <div class="progress-step {{ $stepClass }}" id="step-7">
                                    <span class="{{ $iconClass }}">{!! $iconHtml !!}</span>
                                    <span class="step-label">7. Prediksi Pendapatan</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button id="btnJalankanSvrProses" class="btn btn-dark w-100 py-3 rounded-3 fw-bold fs-6 shadow-sm" style="letter-spacing: 0.5px;" {{ !$datasetReady ? 'disabled' : '' }}>
                                <i class="bi bi-play-fill me-1"></i> Generate Prediksi SVR
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel Informasi Eksekusi -->
            <div class="col-md-7">
                <div class="card h-100 bg-white">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title text-dark"><i class="bi bi-info-square-fill me-2 text-primary-custom"></i>Informasi Eksekusi Model</h5>
                            <div class="alert alert-info border-0 bg-light-subtle rounded-3 py-3 px-3 mb-3 text-sm">
                                <i class="bi bi-lightbulb-fill text-warning me-2"></i>
                                Proses pelatihan akan memakan waktu kurang lebih 5-10 detik. Pastikan layanan Python API berjalan pada port <code>8001</code>.
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-borderless table-sm text-sm mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="fw-semibold text-secondary" style="width: 40%;">Metode Model</td>
                                            <td>: Support Vector Regression (SVR)</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-secondary">Tipe Kernel</td>
                                            <td>: Radial Basis Function (RBF)</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-secondary">Rasio Split Data</td>
                                            <td>: 80% Training (Masa Lalu), 20% Testing (Masa Depan)</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-secondary">Status Terakhir</td>
                                            <td>: 
                                                @if($lastRun)
                                                    <span class="badge bg-success-subtle text-success border-0"><i class="bi bi-check-circle-fill me-1"></i>Sudah Dijalankan</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning border-0"><i class="bi bi-exclamation-triangle-fill me-1"></i>Belum Dijalankan</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($lastRun)
                                            <tr>
                                                <td class="fw-semibold text-secondary">Eksekusi Terakhir</td>
                                                <td>: {{ Carbon\Carbon::parse($lastRun->finished_at)->translatedFormat('d F Y') }}, {{ Carbon\Carbon::parse($lastRun->finished_at)->format('H:i:s') }} WIB</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-secondary">Total Data Diproses</td>
                                                <td>: {{ number_format($lastRun->total_rows, 0, ',', '.') }} baris</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        @if($lastRun)
                            <div class="p-3 bg-success-subtle rounded text-dark small border-0 mt-3 d-flex align-items-center">
                                <i class="bi bi-patch-check-fill text-success me-2 fs-5"></i>
                                <div>
                                    <strong>Model SVR Siap.</strong> Hasil evaluasi model terakhir dapat diakses langsung pada Langkah 4 (Hasil Evaluasi).
                                </div>
                            </div>
                        @else
                            <div class="p-3 bg-light rounded text-muted small border-start border-4 border-warning mt-3 d-flex align-items-center">
                                <i class="bi bi-exclamation-circle-fill text-warning me-2 fs-5"></i>
                                <div>
                                    Silakan klik tombol <strong>Generate Prediksi SVR</strong> untuk melatih model dan melihat hasil evaluasi.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Alur Preprocessing Card -->
        <div class="card mb-4 bg-white shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-dark"><i class="bi bi-diagram-3-fill me-2 text-primary-custom"></i>Detail Alur Preprocessing & Pipeline Data</h5>
                @if(!$lastRun)
                    <div class="text-center py-5">
                        <i class="bi bi-lock-fill text-muted mb-3 d-block" style="font-size: 40px;"></i>
                        <h6 class="fw-semibold text-secondary">Detail Alur Preprocessing Belum Tersedia</h6>
                        <p class="text-muted small mb-0 px-4">Detail alur preprocessing dan pipeline data hanya dapat ditampilkan setelah model SVR berhasil dilatih. Silakan klik tombol <strong>Generate Prediksi SVR</strong> di atas terlebih dahulu.</p>
                    </div>
                @else
                    <p class="text-secondary small mb-4">Klik setiap tahapan di bawah ini untuk melihat detail data sebelum/sesudah transformasi, rumusan, dan contoh visualisasi.</p>
                    
                    <div class="d-flex align-items-start flex-column flex-md-row gap-3">
                    <!-- Tabs List -->
                    <div class="nav flex-column nav-pills me-2 col-12 col-md-4 col-lg-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active" id="v-pills-cleaning-tab" data-bs-toggle="pill" data-bs-target="#v-pills-cleaning" type="button" role="tab" aria-controls="v-pills-cleaning" aria-selected="true">
                            <span class="tab-step-number">1</span> 1. Pembersihan Data
                        </button>
                        <button class="nav-link" id="v-pills-fe-tab" data-bs-toggle="pill" data-bs-target="#v-pills-fe" type="button" role="tab" aria-controls="v-pills-fe" aria-selected="false">
                            <span class="tab-step-number">2</span> 2. Rekayasa Fitur
                        </button>
                        <button class="nav-link" id="v-pills-target-tab" data-bs-toggle="pill" data-bs-target="#v-pills-target" type="button" role="tab" aria-controls="v-pills-target" aria-selected="false">
                            <span class="tab-step-number">3</span> 3. Transformasi Data
                        </button>
                        <button class="nav-link" id="v-pills-normalisasi-tab" data-bs-toggle="pill" data-bs-target="#v-pills-normalisasi" type="button" role="tab" aria-controls="v-pills-normalisasi" aria-selected="false">
                            <span class="tab-step-number">4</span> 4. Normalisasi Data
                        </button>
                        <button class="nav-link" id="v-pills-split-tab" data-bs-toggle="pill" data-bs-target="#v-pills-split" type="button" role="tab" aria-controls="v-pills-split" aria-selected="false">
                            <span class="tab-step-number">5</span> 5. Pembagian Data (Split)
                        </button>
                        <button class="nav-link" id="v-pills-training-tab" data-bs-toggle="pill" data-bs-target="#v-pills-training" type="button" role="tab" aria-controls="v-pills-training" aria-selected="false">
                            <span class="tab-step-number">6</span> 6. Pelatihan Model SVR
                        </button>
                        <button class="nav-link" id="v-pills-prediction-tab" data-bs-toggle="pill" data-bs-target="#v-pills-prediction" type="button" role="tab" aria-controls="v-pills-prediction" aria-selected="false">
                            <span class="tab-step-number">7</span> 7. Prediksi Pendapatan
                        </button>
                    </div>
                    
                    <!-- Tabs Content -->
                    <div class="tab-content flex-grow-1 col-12 col-md-8 col-lg-9 border p-4 rounded-3" id="v-pills-tabContent" style="background-color: #ffffff;">
                        
                        <!-- Tab 1: Pembersihan Data -->
                        <div class="tab-pane fade show active" id="v-pills-cleaning" role="tabpanel" aria-labelledby="v-pills-cleaning-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-dark mb-0">Langkah 1: Pembersihan Data (Data Cleaning)</h5>
                                <span class="badge bg-success-subtle text-success py-1 px-2 text-uppercase fw-bold" style="font-size: 10px;">FILTER & IMPUTATION</span>
                            </div>
                            <p class="text-secondary text-sm">Sebelum pembersihan, dataset asli (data mentah) dibaca dari database. Jika terdapat pencatatan dengan pendapatan bernilai Rp 0, sistem mendeteksi dan membersihkan distorsi tersebut dengan aturan bisnis berikut:</p>
                            
                            <div class="row g-3 mb-4">
                                <!-- Drop Rule -->
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 border border-danger-subtle h-100 bg-white shadow-sm" style="border-left: 4px solid #dc2626 !important;">
                                        <h6 class="fw-bold text-danger mb-2"><i class="bi bi-trash3-fill me-1"></i>1. Penghapusan Baris Pendapatan 0 pada Hari Kerja Biasa</h6>
                                        <p class="text-secondary text-xs mb-0">Baris data bernilai <strong>Rp 0 pada hari kerja biasa</strong> (bukan libur nasional) akan <strong>dihapus (dropped)</strong> karena diindikasikan sebagai kesalahan pencatatan atau juru parkir absen menyetor.</p>
                                    </div>
                                </div>
                                <!-- Impute Rule -->
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 border border-success-subtle h-100 bg-white shadow-sm" style="border-left: 4px solid #16a34a !important;">
                                        <h6 class="fw-bold text-success mb-2"><i class="bi bi-magic me-1"></i>2. Imputasi Median pada Hari Libur Nasional</h6>
                                        <p class="text-secondary text-xs mb-0">Jika pendapatan Rp 0 terjadi pada <strong>Hari Libur Nasional</strong>, data tidak dihapus, melainkan diisi (imputasi) menggunakan <strong>nilai median pendapatan hari libur</strong> yang bernilai positif.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-3">
                                <!-- Table 1: Raw Snapshot -->
                                <div class="col-12 mb-3">
                                    <div class="card border border-light h-100 mb-0 shadow-sm">
                                        <div class="card-header bg-light py-2">
                                            <span class="fw-semibold text-secondary text-xs"><i class="bi bi-table me-1"></i>Contoh Struktur Dataset Asli (Raw)</span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0 table-custom-nowrap table-preview-custom">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Tanggal</th>
                                                        <th>Rayon</th>
                                                        <th class="text-center">Weekend</th>
                                                        <th class="text-center">Libur</th>
                                                        <th class="text-end">Pendapatan Awal</th>
                                                        <th class="text-end">Jukir</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($rawSnapshot as $snap)
                                                        <tr>
                                                            <td><code>{{ $snap['tanggal'] }}</code></td>
                                                            <td><span class="badge bg-light text-dark border">{{ $snap['rayon_name'] }}</span></td>
                                                            <td class="text-center">
                                                                <span class="badge {{ $snap['weekend'] ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary' }} border-0 py-1 px-2">
                                                                    {{ $snap['weekend'] }}
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge {{ $snap['libur_nasional'] ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary' }} border-0 py-1 px-2">
                                                                    {{ $snap['libur_nasional'] }}
                                                                </span>
                                                            </td>
                                                            <td class="text-end fw-semibold text-dark">Rp {{ number_format($snap['total_pendapatan'], 0, ',', '.') }}</td>
                                                            <td class="text-end text-secondary">{{ $snap['jumlah_jukir'] }} Jukir</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="text-center py-3 text-muted">Snapshot data pendapatan kosong.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Table 2: Cleaning Simulation -->
                                <div class="col-12">
                                    <div class="card border border-light h-100 mb-0 shadow-sm">
                                        <div class="card-header bg-light py-2">
                                            <span class="fw-semibold text-secondary text-xs"><i class="bi bi-shield-check me-1"></i>Simulasi Pembersihan Data</span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0 table-custom-nowrap table-preview-custom">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Kondisi / Kasus</th>
                                                        <th>Tanggal</th>
                                                        <th class="text-center">Weekend</th>
                                                        <th class="text-center">Libur</th>
                                                        <th class="text-end">Pendapatan</th>
                                                        <th class="text-center">Aksi Preprocessing</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr style="background-color: rgba(220, 38, 38, 0.02);">
                                                        <td><span class="text-danger fw-semibold">Kerja biasa Rp 0</span></td>
                                                        <td><code>2024-03-05</code></td>
                                                        <td class="text-center">0</td>
                                                        <td class="text-center">0</td>
                                                        <td class="text-end text-danger fw-semibold">Rp 0</td>
                                                        <td class="text-center"><span class="badge bg-danger py-1 px-2">BARIS DIHAPUS (DROP)</span></td>
                                                    </tr>
                                                    <tr style="background-color: rgba(22, 163, 74, 0.02);">
                                                        <td><span class="text-success fw-semibold">Hari libur Rp 0</span></td>
                                                        <td><code>2024-04-10</code></td>
                                                        <td class="text-center">0</td>
                                                        <td class="text-center">1</td>
                                                        <td class="text-end text-danger fw-semibold">Rp 0</td>
                                                        <td class="text-center"><span class="badge bg-success py-1 px-2">IMPUTASI MEDIAN (FILL)</span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab 2: Rekayasa Fitur -->
                        <div class="tab-pane fade" id="v-pills-fe" role="tabpanel" aria-labelledby="v-pills-fe-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-dark mb-0">Langkah 2: Rekayasa Fitur (Feature Engineering)</h5>
                                <span class="badge bg-primary-subtle text-primary-custom py-1 px-2 text-uppercase fw-bold" style="font-size: 10px;">FEATURE ENGINEERING</span>
                            </div>
                            <p class="text-secondary text-sm">Menambah dimensi fitur masukan agar model SVR mampu menangkap pengaruh tren waktu (temporal), efek hari libur, serta karakteristik spasial (rayon):</p>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card h-100 mb-0 border bg-white p-3 shadow-sm">
                                        <h6 class="fw-bold text-dark text-sm mb-2"><i class="bi bi-calendar3 text-primary-custom me-1"></i>Fitur Temporal & Siklikal</h6>
                                        <p class="text-muted text-xs mb-0">Ekstraksi hari, bulan, tahun, nomor minggu, serta transformasi sine & cosine pada hari-dalam-minggu dan tanggal-kalender untuk menangkap kontinuitas siklus waktu.</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100 mb-0 border bg-white p-3 shadow-sm">
                                        <h6 class="fw-bold text-dark text-sm mb-2"><i class="bi bi-clock-history text-primary-custom me-1"></i>Fitur Lag & Rolling Mean</h6>
                                        <p class="text-muted text-xs mb-0">Menyisipkan variabel historis <code>Lag_1</code>, <code>Lag_7</code>, <code>Lag_14</code>, dan <code>Lag_21</code> (pendapatan pada hari-hari sebelumnya) serta rolling mean 7 dan 30 hari untuk mendeteksi tren terkini.</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100 mb-0 border bg-white p-3 shadow-sm">
                                        <h6 class="fw-bold text-dark text-sm mb-2"><i class="bi bi-geo-alt-fill text-primary-custom me-1"></i>Rayon Dummy & Interaksi</h6>
                                        <p class="text-muted text-xs mb-0">One-hot encoding data Rayon menjadi 5 kolom dummy terpisah (Rayon_1 s.d Rayon_5) serta perkalian interaksi <code>Weekend * Rayon_X</code> untuk melihat perbedaan efek libur di tiap rayon.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab 3: Transformasi Data -->
                        <div class="tab-pane fade" id="v-pills-target" role="tabpanel" aria-labelledby="v-pills-target-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-dark mb-0">Langkah 3: Transformasi Data</h5>
                                <span class="badge bg-warning-subtle text-warning py-1 px-2 text-uppercase fw-bold" style="font-size: 10px;">DATA TRANSFORMATION</span>
                            </div>
                            <p class="text-secondary text-sm">Distribusi data retribusi parkir harian memiliki tingkat kemiringan (skewness) tinggi dengan pencilan ekstrem. Dilakukan transformasi target untuk menstabilkan variansi model:</p>
                            
                            <div class="bg-white p-3 rounded-3 border mb-3 shadow-sm">
                                <h6 class="fw-bold text-dark mb-2 text-sm"><i class="bi bi-calculator me-1"></i>Rumus Transformasi Logaritmik:</h6>
                                <div class="text-center py-2 bg-light rounded my-2">
                                    <code class="fs-5 text-dark">y_transformed = ln(y + 1)</code>
                                </div>
                                <p class="text-secondary text-xs mb-0">Menggunakan fungsi <code>log1p</code> untuk menangani nilai nol dengan aman. Setelah prediksi selesai didapatkan dari model SVR, nilai dikembalikan ke rupiah asli menggunakan fungsi inverse eksponensial <code>expm1</code>: <code>y_original = exp(y_predicted) - 1</code>.</p>
                            </div>
                        </div>
                        
                        <!-- Tab 4: Normalisasi Data -->
                        <div class="tab-pane fade" id="v-pills-normalisasi" role="tabpanel" aria-labelledby="v-pills-normalisasi-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-dark mb-0">Langkah 4: Normalisasi Data</h5>
                                <span class="badge bg-info-subtle text-info py-1 px-2 text-uppercase fw-bold" style="font-size: 10px;">SCALING & NORMALIZATION</span>
                            </div>
                            <p class="text-secondary text-sm">Menyamakan skala nilai seluruh fitur masukan (X) dan target (y) agar fungsi kernel dan batas toleransi kesalahan SVR dapat dikalkulasi secara optimal:</p>
                            
                            <ul class="list-group list-group-flush mb-0 text-sm bg-white p-3 border rounded shadow-sm">
                                <li class="list-group-item bg-transparent px-0 py-2.5">
                                    <strong>RobustScaler (pada Fitur Masukan X)</strong>: Menskalakan fitur menggunakan rentang Median dan Interquartile Range (IQR). Sangat direkomendasikan karena kebal terhadap outlier (nilai ekstrem) pendapatan parkir pada hari libur besar.
                                </li>
                                <li class="list-group-item bg-transparent px-0 py-2.5">
                                    <strong>MinMaxScaler (pada Target y)</strong>: Menskalakan target logaritmik pendapatan ke rentang <code>[0, 1]</code> untuk menjaga kestabilan nilai gradien bobot penalti SVR.
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Tab 5: Pembagian Data -->
                        <div class="tab-pane fade" id="v-pills-split" role="tabpanel" aria-labelledby="v-pills-split-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-dark mb-0">Langkah 5: Pembagian Data (Split Data 80:20)</h5>
                                <span class="badge bg-danger-subtle text-danger py-1 px-2 text-uppercase fw-bold" style="font-size: 10px;">DATA SPLITTING (80:20)</span>
                            </div>
                            <p class="text-secondary text-sm">Dataset dibagi menjadi data training (80%) untuk melatih model dan data testing (20%) untuk menguji performa prediksi peramalan.</p>
                            
                            <div class="p-3 bg-white border rounded-3 mb-0 shadow-sm" style="border-left: 4px solid var(--primary-blue) !important;">
                                <h6 class="fw-bold text-dark mb-2 text-sm"><i class="bi bi-clock-fill me-1 text-primary-custom"></i>Aturan Time-Series Split (Kronologis)</h6>
                                <p class="text-secondary text-xs mb-0">Pembagian data <strong>WAJIB dilakukan secara urut waktu (kronologis)</strong>, bukan secara acak (random train-test split). Hal ini krusial untuk mencegah terjadinya kebocoran data (data leakage) dari masa depan ke masa lalu, sehingga menjamin validitas hasil pengujian peramalan.</p>
                            </div>
                        </div>
                        
                        <!-- Tab 6: Pelatihan Model SVR -->
                        <div class="tab-pane fade" id="v-pills-training" role="tabpanel" aria-labelledby="v-pills-training-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-dark mb-0">Langkah 6: Pelatihan Model SVR</h5>
                                <span class="badge bg-dark text-white py-1 px-2 text-uppercase fw-bold" style="font-size: 10px;">SVR MODEL TRAINING</span>
                            </div>
                            <p class="text-secondary text-sm">Model di-fitting menggunakan algoritma Support Vector Regression (SVR) standar pada data training yang telah dinormalisasi:</p>
                            
                            <ul class="list-group list-group-flush text-sm mb-0 bg-white p-3 border rounded shadow-sm">
                                <li class="list-group-item bg-transparent px-0 py-2"><strong>Tipe Kernel</strong>: Radial Basis Function (RBF) untuk pola non-linear.</li>
                                <li class="list-group-item bg-transparent px-0 py-2"><strong>Regularisasi (C)</strong>: <code>1.0</code> (Nilai default).</li>
                                <li class="list-group-item bg-transparent px-0 py-2"><strong>Epsilon Toleransi (&epsilon;)</strong>: <code>0.1</code> (Batas lebar tabung toleransi kesalahan).</li>
                                <li class="list-group-item bg-transparent px-0 py-2"><strong>Gamma (&gamma;)</strong>: <code>scale</code> (Koefisien default Scikit-Learn).</li>
                            </ul>
                        </div>

                        <!-- Tab 7: Prediksi Pendapatan -->
                        <div class="tab-pane fade" id="v-pills-prediction" role="tabpanel" aria-labelledby="v-pills-prediction-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-dark mb-0">Langkah 7: Prediksi Pendapatan</h5>
                                <span class="badge bg-dark text-white py-1 px-2 text-uppercase fw-bold" style="font-size: 10px;">PREDICTION & DATABASE INSERT</span>
                            </div>
                            <p class="text-secondary text-sm">Setelah model SVR selesai dilatih pada data training, langkah terakhir adalah memprediksi nilai pendapatan pada data testing (20% data terbaru) dan menyimpannya kembali ke database:</p>
                            
                            <ul class="list-group list-group-flush text-sm mb-0 bg-white p-3 border rounded shadow-sm">
                                <li class="list-group-item bg-transparent px-0 py-2.5">
                                    <strong>Inverse Scaling & Transformation</strong>: Hasil keluaran model SVR yang masih dalam skala normalisasi `[0, 1]` dan logaritmik ditransformasikan kembali ke skala rupiah asli menggunakan fungsi inverse scaling (MinMaxScaler inverse) diikuti oleh fungsi eksponensial <code>expm1(y) = e^y - 1</code>.
                                </li>
                                <li class="list-group-item bg-transparent px-0 py-2.5">
                                    <strong>Evaluasi Kinerja Model</strong>: Mengkalkulasi metrik evaluasi standar seperti MAE, RMSE, MAPE, dan R² score pada data testing untuk mengukur keakuratan peramalan model.
                                </li>
                                <li class="list-group-item bg-transparent px-0 py-2.5">
                                    <strong>Penyimpanan Database</strong>: Menyimpan seluruh riwayat model running parameter, metrik pengujian, dan hasil peramalan per tanggal dan per rayon ke dalam database agar dapat ditampilkan secara visual dan diexport.
                                </li>
                            </ul>
                        </div>
                        
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Navigation Footer for Step 3 -->
        <div class="d-flex justify-content-between mb-4">
            <button class="btn btn-outline-secondary px-4 py-2.5 rounded-3 fw-semibold" onclick="goToStep(2)">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Konfigurasi SVR
            </button>
            <button class="btn btn-dark px-4 py-2.5 rounded-3 fw-semibold shadow-sm" onclick="goToStep(4)">
                Lihat Hasil Evaluasi <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>

    <!-- STEP 4 CONTENT -->
    <div id="step-content-4" class="step-content-section d-none">
        @php
            $rayonStats = collect([]);
            $bestRayon = null;
            $worstRayon = null;
            $avgDailyDeviation = 0;
            
            $maxActualDate = '-';
            $maxActualVal = 0;
            $maxPredictedDate = '-';
            $maxPredictedVal = 0;
            
            $totalActualSum = 0;
            $totalPredictedSum = 0;
            $totalDiff = 0;
            $totalDiffPercent = 0;
            $predictedAtMaxActual = 0;
            $maxActualAccuracy = 0;
            $actualAtMaxPredicted = 0;
            
            if ($lastRun) {
                $rayonStats = $lastRun->predictionResults()
                    ->select('rayon_name', 
                        DB::raw('AVG(percentage_error) as avg_mape'), 
                        DB::raw('AVG(error_value) as avg_error'),
                        DB::raw('SUM(actual_value) as total_actual'), 
                        DB::raw('SUM(predicted_value) as total_predicted')
                    )
                    ->groupBy('rayon_name')
                    ->get();
                
                $bestRayon = $rayonStats->sortBy('avg_mape')->first();
                $worstRayon = $rayonStats->sortByDesc('avg_mape')->first();
                
                $avgDailyDeviation = $lastRun->predictionResults()->avg('error_value') ?? 0;
                
                if ($chartData->count() > 0) {
                    $maxActualRow = $chartData->sortByDesc('actual_value')->first();
                    $maxPredictedRow = $chartData->sortByDesc('predicted_value')->first();
                    
                    $maxActualDate = $maxActualRow ? Carbon\Carbon::parse($maxActualRow->tanggal)->translatedFormat('d F Y') : '-';
                    $maxActualVal = $maxActualRow ? $maxActualRow->actual_value : 0;
                    
                    $maxPredictedDate = $maxPredictedRow ? Carbon\Carbon::parse($maxPredictedRow->tanggal)->translatedFormat('d F Y') : '-';
                    $maxPredictedVal = $maxPredictedRow ? $maxPredictedRow->predicted_value : 0;
                    
                    $predictedAtMaxActual = $maxActualRow ? $maxActualRow->predicted_value : 0;
                    $actualAtMaxPredicted = $maxPredictedRow ? $maxPredictedRow->actual_value : 0;
                    $maxActualAccuracy = $maxActualVal > 0 ? (1 - abs($maxActualVal - $predictedAtMaxActual) / $maxActualVal) * 100 : 0;
                    
                    $totalActualSum = $chartData->sum('actual_value');
                    $totalPredictedSum = $chartData->sum('predicted_value');
                    $totalDiff = abs($totalActualSum - $totalPredictedSum);
                    $totalDiffPercent = $totalActualSum > 0 ? ($totalDiff / $totalActualSum) * 100 : 0;
                }
            }
        @endphp
        <!-- 4. Ringkasan Sukses Eksekusi Terakhir -->
    @if($lastRun)
        <div class="card mb-4 bg-white">
            <div class="card-body">
                <h5 class="card-title text-success"><i class="bi bi-check-circle-fill me-2"></i>Ringkasan Eksekusi Model SVR Terakhir</h5>
                <div class="row g-3 small">
                    <div class="col-6 col-md-3 border-end border-light">
                        <span class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 9.5px; letter-spacing: 0.5px;">Jumlah Data</span>
                        <strong class="fs-6 text-dark">{{ number_format($lastRun->total_rows, 0, ',', '.') }} baris</strong>
                    </div>
                    <div class="col-6 col-md-3 border-end border-light">
                        <span class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 9.5px; letter-spacing: 0.5px;">Data Training (80%)</span>
                        <strong class="text-dark d-block mb-1">{{ number_format($lastRun->train_rows, 0, ',', '.') }} baris</strong>
                        <span class="text-muted" style="font-size: 10px;">Periode: {{ $lastRun->train_period }}</span>
                    </div>
                    <div class="col-6 col-md-3 border-end border-light">
                        <span class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 9.5px; letter-spacing: 0.5px;">Data Testing (20%)</span>
                        <strong class="text-dark d-block mb-1">{{ number_format($lastRun->test_rows, 0, ',', '.') }} baris</strong>
                        <span class="text-muted" style="font-size: 10px;">Periode: {{ $lastRun->test_period }}</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 9.5px; letter-spacing: 0.5px;">Waktu Penyelesaian</span>
                        <strong class="text-dark d-block">{{ Carbon\Carbon::parse($lastRun->finished_at)->translatedFormat('d F Y') }}</strong>
                        <span class="text-muted d-block" style="font-size: 10px;">Jam: {{ Carbon\Carbon::parse($lastRun->finished_at)->format('H:i:s') }} WIB</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 5. Section Hasil Evaluasi Model & Visualisasi (Hanya jika lastRun sukses) -->
    @if(!$lastRun)
        <div class="card text-center py-5 shadow-sm border border-light bg-white">
            <div class="card-body py-4">
                <i class="bi bi-graph-up-arrow text-secondary mb-3 d-block" style="font-size: 40px;"></i>
                <h5 class="fw-semibold text-secondary">Belum Ada Hasil Prediksi</h5>
                <p class="text-muted small mb-0">Belum ada hasil prediksi tersimpan. Silakan jalankan <strong>Generate Prediksi SVR</strong> terlebih dahulu.</p>
            </div>
        </div>
    @else
        <!-- Card Metrik Evaluasi Model SVR -->
        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-award-fill me-2 text-primary"></i>Hasil Evaluasi Model SVR</h5>
        <div class="row g-3 mb-4">
            <!-- MAE -->
            <div class="col-12 col-md-4 col-lg">
                <div class="metric-card-custom">
                    <span class="metric-label-custom">Mean Absolute Error (MAE)</span>
                    <span class="metric-value-custom">Rp {{ number_format($metrics->mae, 0, ',', '.') }}</span>
                    <span class="text-muted small" style="font-size: 11px;">Rata-rata absolut selisih error</span>
                </div>
            </div>
            <!-- RMSE -->
            <div class="col-12 col-md-4 col-lg">
                <div class="metric-card-custom">
                    <span class="metric-label-custom">Root Mean Squared Error (RMSE)</span>
                    <span class="metric-value-custom">Rp {{ number_format($metrics->rmse, 0, ',', '.') }}</span>
                    <span class="text-muted small" style="font-size: 11px;">Akar kuadrat rata-rata error kuadrat</span>
                </div>
            </div>
            <!-- Akurasi MAPE -->
            <div class="col-12 col-md-4 col-lg">
                <div class="metric-card-custom">
                    <span class="metric-label-custom">Akurasi MAPE</span>
                    <span class="metric-value-custom text-success">{{ number_format($metrics->accuracy, 2, ',', '.') }}%</span>
                    @php
                        $mapeVal = $metrics->mape;
                        $mapeInterpret = 'Cukup / Reasonable';
                        $mapeClass = 'bg-warning-subtle text-warning';
                        if ($mapeVal < 10) {
                            $mapeInterpret = 'Sangat Akurat';
                            $mapeClass = 'bg-success-subtle text-success';
                        } elseif ($mapeVal < 20) {
                            $mapeInterpret = 'Baik / Good';
                            $mapeClass = 'bg-success-subtle text-success';
                        } elseif ($mapeVal > 50) {
                            $mapeInterpret = 'Lemah / Weak';
                            $mapeClass = 'bg-danger-subtle text-danger';
                        }
                    @endphp
                    <div>
                        <span class="badge border-0 {{ $mapeClass }} py-1 px-2.5" style="font-size: 9.5px; font-weight: 600;">{{ $mapeInterpret }}</span>
                    </div>
                </div>
            </div>
            <!-- R2 Score -->
            <div class="col-12 col-md-6 col-lg">
                <div class="metric-card-custom">
                    <span class="metric-label-custom">R² Score</span>
                    <span class="metric-value-custom">{{ number_format($metrics->r2_score, 6, ',', '.') }}</span>
                    @php
                        $r2Val = $metrics->r2_score;
                        $r2Interpret = 'Lemah';
                        $r2Class = 'bg-danger-subtle text-danger';
                        if ($r2Val >= 0.67) {
                            $r2Interpret = 'Kuat';
                            $r2Class = 'bg-success-subtle text-success';
                        } elseif ($r2Val >= 0.33) {
                            $r2Interpret = 'Moderat';
                            $r2Class = 'bg-primary-subtle text-primary';
                        }
                    @endphp
                    <div>
                        <span class="badge border-0 {{ $r2Class }} py-1 px-2.5" style="font-size: 9.5px; font-weight: 600;">{{ $r2Interpret }}</span>
                    </div>
                </div>
            </div>
            <!-- MAPE detail percentage -->
            <div class="col-12 col-md-6 col-lg">
                <div class="metric-card-custom">
                    <span class="metric-label-custom">Persentase MAPE</span>
                    <span class="metric-value-custom text-secondary">{{ number_format($metrics->mape, 4, ',', '.') }}%</span>
                    <span class="text-muted small" style="font-size: 11px;">Rata-rata persentase error</span>
                </div>
            </div>
        </div>

        <!-- Card Analisis & Rekomendasi Model -->
        <div class="card mb-4 bg-white shadow-sm border border-light">
            <div class="card-body">
                <h5 class="card-title text-dark mb-3"><i class="bi bi-chat-left-text-fill me-2 text-primary-custom"></i>Analisis Kinerja & Rekomendasi Model</h5>
                
                @php
                    $mape = $metrics->mape;
                    $r2 = $metrics->r2_score;
                    $rmse = $metrics->rmse;
                    $mae = $metrics->mae;
                    
                    // Hitung Rata-rata Aktual untuk RMSE
                    $meanActual = $lastRun->predictionResults()->avg('actual_value') ?? 0;
                    $rmsePercentage = $meanActual > 0 ? ($rmse / $meanActual) * 100 : 0;
                    
                    // 1. Klasifikasi MAPE
                    if ($mape < 10) {
                        $mapeCategory = "Sangat Akurat (Excellent)";
                        $mapeDesc = "Nilai MAPE < 10% diklasifikasikan sebagai <strong>Sangat Akurat (Excellent)</strong>. Deviasi hasil prediksi sangat kecil terhadap data aktual.";
                        $mapeColor = "text-success border-success bg-success-subtle";
                        $mapeAlertClass = "alert-success text-success-emphasis bg-success-subtle border-success-subtle";
                        $mapeIcon = "bi-patch-check-fill text-success";
                    } elseif ($mape <= 20) {
                        $mapeCategory = "Baik (Good)";
                        $mapeDesc = "Nilai MAPE 10% - 20% diklasifikasikan sebagai <strong>Baik (Good)</strong>. Hasil peramalan dinilai handal dan layak digunakan.";
                        $mapeColor = "text-primary border-primary bg-primary-subtle";
                        $mapeAlertClass = "alert-primary text-primary-emphasis bg-primary-subtle border-primary-subtle";
                        $mapeIcon = "bi-check-circle-fill text-primary";
                    } elseif ($mape <= 50) {
                        $mapeCategory = "Cukup (Reasonable)";
                        $mapeDesc = "Nilai MAPE 20% - 50% diklasifikasikan sebagai <strong>Cukup (Reasonable)</strong>. Perlu dicatat terdapat fluktuasi moderat pada beberapa titik data.";
                        $mapeColor = "text-warning border-warning bg-warning-subtle";
                        $mapeAlertClass = "alert-warning text-warning-emphasis bg-warning-subtle border-warning-subtle";
                        $mapeIcon = "bi-exclamation-triangle-fill text-warning";
                    } else {
                        $mapeCategory = "Buruk (Inaccurate)";
                        $mapeDesc = "Nilai MAPE > 50% diklasifikasikan sebagai <strong>Buruk (Inaccurate)</strong>. Model peramalan kurang andal karena simpangan tinggi.";
                        $mapeColor = "text-danger border-danger bg-danger-subtle";
                        $mapeAlertClass = "alert-danger text-danger-emphasis bg-danger-subtle border-danger-subtle";
                        $mapeIcon = "bi-x-circle-fill text-danger";
                    }

                    // 2. Klasifikasi R2 Score
                    if ($r2 >= 0.67) {
                        $r2Category = "Model Kuat (Strong)";
                        $r2Desc = "Nilai R² Score 0.67 - 1.00 diklasifikasikan sebagai <strong>Model Kuat (Strong)</strong>. Model mampu menjelaskan variabilitas data secara optimal.";
                        $r2Icon = "bi-graph-up text-success";
                    } elseif ($r2 >= 0.33) {
                        $r2Category = "Model Moderat";
                        $r2Desc = "Nilai R² Score 0.33 - 0.67 diklasifikasikan sebagai <strong>Model Moderat</strong>. Variasi data sebagian dipengaruhi oleh faktor di luar model.";
                        $r2Icon = "bi-graph-up text-primary";
                    } else {
                        $r2Category = "Model Lemah";
                        $r2Desc = "Nilai R² Score < 0.33 diklasifikasikan sebagai <strong>Model Lemah</strong>. Model kesulitan menangkap variasi/pola dalam data retribusi.";
                        $r2Icon = "bi-graph-up text-danger";
                    }
                    
                    // 3. Klasifikasi RMSE
                    if ($rmsePercentage < 10) {
                        $rmseCategory = "Sangat Baik";
                        $rmseDesc = "Nilai RMSE (Rp " . number_format($rmse, 0, ',', '.') . ") berada di bawah 10% dari nilai rata-rata aktual (Rp " . number_format($meanActual, 0, ',', '.') . "), yaitu sebesar <strong>" . number_format($rmsePercentage, 2, ',', '.') . "%</strong>. Kinerja dikategorikan <strong>Sangat Baik</strong>.";
                        $rmseColor = "text-success";
                        $rmseIcon = "bi-shield-check-fill text-success";
                    } else {
                        $rmseCategory = "Perlu Optimasi";
                        $rmseDesc = "Nilai RMSE (Rp " . number_format($rmse, 0, ',', '.') . ") bernilai sebesar <strong>" . number_format($rmsePercentage, 2, ',', '.') . "%</strong> dari rata-rata data aktual (Rp " . number_format($meanActual, 0, ',', '.') . "). Deviasi varian error melampaui 10% ambang batas ideal.";
                        $rmseColor = "text-warning";
                        $rmseIcon = "bi-exclamation-octagon-fill text-warning";
                    }
                    
                    // 4. Klasifikasi MAE
                    $maeCategory = "Presisi Tinggi";
                    $maeDesc = "Nilai MAE sebesar Rp " . number_format($mae, 0, ',', '.') . " menunjukkan rata-rata error absolut. Semakin mendekati nilai 0 menunjukkan tingkat <strong>Presisi Tinggi</strong>.";
                    $maeIcon = "bi-pin-map-fill text-primary";

                    // 5. Rekomendasi berdasarkan kombinasi nilai
                    $recommendations = [];
                    if ($mape < 10 && $r2 >= 0.67) {
                        $recommendations[] = "<strong>Model Siap Digunakan:</strong> Kinerja model SVR ini sangat baik dengan tingkat kesalahan sangat rendah. Sangat layak digunakan untuk mendukung pengambilan keputusan penetapan target retribusi.";
                        $recommendations[] = "<strong>Pertahankan Parameter Default:</strong> Parameter aktif saat ini sudah optimal untuk dataset saat ini. Pemantauan berkala direkomendasikan tanpa perlu optimasi mendesak.";
                    } else {
                        $recommendations[] = "<strong>Lakukan Optimasi Parameter:</strong> Jalankan penyetelan hyperparameter (C, Epsilon, Gamma) menggunakan algoritma <strong>Grey Wolf Optimizer (GWO)</strong> atau <strong>Grid Search</strong> di menu Optimasi Parameter untuk menekan error.";
                        $recommendations[] = "<strong>Tambahkan Data Historis:</strong> Jika akurasi belum optimal, pertimbangkan untuk memperpanjang rentang data training historis pendapatan agar model dapat belajar lebih komprehensif.";
                    }
                    
                    $recommendations[] = "<strong>Retraining Model Berkala:</strong> Lakukan pelatihan ulang (Generate Prediksi) secara rutin setiap ada penambahan data transaksi pendapatan harian terbaru agar model tetap adaptif terhadap tren terbaru.";
                @endphp

                <div class="row g-3">
                    <!-- Penjelasan Analisis -->
                    <div class="col-md-7">
                        <h6 class="fw-bold text-secondary text-uppercase mb-2 shadow-none border-0 pb-0" style="font-size: 11px; letter-spacing: 0.5px;">Keterangan Hasil Analisis</h6>
                        <div class="d-flex flex-column gap-3">
                            <!-- Card MAPE -->
                            <div class="p-3 rounded-3 border border-light bg-light-subtle d-flex gap-3">
                                <div class="fs-4"><i class="bi {{ $mapeIcon }}"></i></div>
                                <div>
                                    <div class="fw-bold text-dark mb-1" style="font-size: 13.5px;">Akurasi MAPE ({{ number_format($mape, 2, ',', '.') }}%): <span class="{{ explode(' ', $mapeColor)[0] }}">{{ $mapeCategory }}</span></div>
                                    <div class="text-secondary small" style="line-height: 1.5;">{!! $mapeDesc !!}</div>
                                </div>
                            </div>
                            
                            <!-- Card R2 -->
                            <div class="p-3 rounded-3 border border-light bg-light-subtle d-flex gap-3">
                                <div class="fs-4"><i class="bi {{ $r2Icon }}"></i></div>
                                <div>
                                    <div class="fw-bold text-dark mb-1" style="font-size: 13.5px;">R² Score ({{ number_format($r2, 4, ',', '.') }}): <span class="text-dark">{{ $r2Category }}</span></div>
                                    <div class="text-secondary small" style="line-height: 1.5;">{!! $r2Desc !!}</div>
                                </div>
                            </div>

                            <!-- Card RMSE -->
                            <div class="p-3 rounded-3 border border-light bg-light-subtle d-flex gap-3">
                                <div class="fs-4"><i class="bi {{ $rmseIcon }}"></i></div>
                                <div>
                                    <div class="fw-bold text-dark mb-1" style="font-size: 13.5px;">RMSE: <span class="{{ $rmseColor }}">{{ $rmseCategory }}</span></div>
                                    <div class="text-secondary small" style="line-height: 1.5;">{!! $rmseDesc !!}</div>
                                </div>
                            </div>

                            <!-- Card MAE -->
                            <div class="p-3 rounded-3 border border-light bg-light-subtle d-flex gap-3">
                                <div class="fs-4"><i class="bi {{ $maeIcon }}"></i></div>
                                <div>
                                    <div class="fw-bold text-dark mb-1" style="font-size: 13.5px;">MAE: <span class="text-primary-custom">{{ $maeCategory }}</span></div>
                                    <div class="text-secondary small" style="line-height: 1.5;">{!! $maeDesc !!}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rekomendasi Tindakan -->
                    <div class="col-md-5">
                        <div class="p-3 rounded-3 h-100 {{ $mapeAlertClass }} border border-0">
                            <h6 class="fw-bold text-uppercase mb-3 d-flex align-items-center" style="font-size: 11px; letter-spacing: 0.5px;">
                                <i class="bi bi-lightbulb-fill me-2 fs-5"></i>Rekomendasi Tindakan
                            </h6>
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-3" style="font-size: 12.5px; line-height: 1.6;">
                                @foreach($recommendations as $rec)
                                    <li class="d-flex align-items-start gap-2">
                                        <i class="bi bi-check2-circle mt-0.5 flex-shrink-0"></i>
                                        <span>{!! $rec !!}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Grafik & Tabel Section - Ditumpuk Vertikal agar Rapi -->
        <div class="row g-4 mb-4">
            <!-- Grafik Line Chart (Lebar Penuh) -->
            <div class="col-12">
                <div class="card mb-0 bg-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-graph-up-arrow me-2 text-primary-custom"></i>Grafik Aktual vs Prediksi Model SVR</h5>
                        <div style="height: 380px; position: relative; width: 100%;">
                            <canvas id="svrChart"></canvas>
                        </div>
                        
                        <!-- Detailed Graph Analysis Card -->
                        <div class="mt-4 p-3 bg-light rounded-3 border-start border-4 border-primary shadow-sm">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle-fill text-primary-custom me-1"></i>Analisis Pola & Kesesuaian Tren Grafik</h6>
                            <div class="row g-3 mt-1 text-sm text-secondary">
                                <div class="col-md-6 border-end border-light-subtle">
                                    <div class="mb-2">
                                        <i class="bi bi-arrow-repeat text-primary-custom me-1"></i>
                                        <strong>Tingkat Kesesuaian Pola:</strong> 
                                        @if($totalDiffPercent < 5)
                                            Sangat Konvergen (Deviasi Akumulasi hanya <strong>{{ number_format($totalDiffPercent, 2, ',', '.') }}%</strong>). Model SVR mampu merekonstruksi pergerakan tren pendapatan harian secara sangat presisi.
                                        @elseif($totalDiffPercent < 15)
                                            Konvergen Baik (Deviasi Akumulasi <strong>{{ number_format($totalDiffPercent, 2, ',', '.') }}%</strong>). Pola peramalan mengikuti fluktuasi data riil dengan baik.
                                        @else
                                            Deviasi Cukup Tinggi (Deviasi Akumulasi <strong>{{ number_format($totalDiffPercent, 2, ',', '.') }}%</strong>). Model perlu penyesuaian parameter untuk menekan simpangan tren.
                                        @endif
                                    </div>
                                    <div>
                                        <i class="bi bi-calendar-check text-primary-custom me-1"></i>
                                        <strong>Puncak Realisasi (Aktual):</strong> 
                                        Terjadi pada tanggal <strong>{{ $maxActualDate }}</strong> sebesar <strong>Rp {{ number_format($maxActualVal, 0, ',', '.') }}</strong>, di mana model memproyeksikan <strong>Rp {{ number_format($predictedAtMaxActual, 0, ',', '.') }}</strong> (akurasi <strong>{{ number_format($maxActualAccuracy, 2, ',', '.') }}%</strong> pada hari puncak).
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <i class="bi bi-graph-up-arrow text-warning me-1"></i>
                                        <strong>Puncak Peramalan (Prediksi SVR):</strong> 
                                        Diproyeksikan pada tanggal <strong>{{ $maxPredictedDate }}</strong> sebesar <strong>Rp {{ number_format($maxPredictedVal, 0, ',', '.') }}</strong> (realisasi aktual hari tersebut: <strong>Rp {{ number_format($actualAtMaxPredicted, 0, ',', '.') }}</strong>).
                                    </div>
                                    <div>
                                        <i class="bi bi-lightning-fill text-warning me-1"></i>
                                        <strong>Sensitivitas Temporal:</strong> 
                                        Model responsif menangkap pola musiman harian. Selisih total volume transaksi kumulatif aktual vs prediksi selama seluruh periode testing (85 hari data) adalah sebesar <strong>Rp {{ number_format($totalDiff, 0, ',', '.') }}</strong> (hanya <strong>{{ number_format($totalDiffPercent, 2, ',', '.') }}%</strong> dari total realisasi kumulatif sebesar <strong>Rp {{ number_format($totalActualSum, 0, ',', '.') }}</strong>).
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Data Prediksi (Lebar Penuh) -->
            <div class="col-12">
                <div class="card mb-0 bg-white">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0 border-0 pb-0"><i class="bi bi-table me-2 text-primary-custom"></i>Tabel Hasil Prediksi (Data Testing)</h5>
                                
                                <!-- Rayon Filter Form -->
                                <form id="filterRayonForm" method="GET" action="{{ route('operator.prediksi.index') }}" class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="active_step" value="4">
                                    <label for="rayon_id" class="small fw-semibold text-secondary text-nowrap mb-0" style="font-size: 11.5px;">Filter Rayon:</label>
                                    <select id="rayon_id" name="rayon_id" class="form-select form-select-sm" style="font-size: 12px; padding: 4px 12px; height: 32px;" onchange="this.form.submit()">
                                        <option value="0">Semua Rayon</option>
                                        @foreach($rayons as $rayon)
                                            <option value="{{ $rayon->id }}" {{ request('rayon_id') == $rayon->id ? 'selected' : '' }}>{{ $rayon->nama_rayon }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 table-custom-nowrap" style="font-size: 13px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Rayon</th>
                                            <th class="text-end">Realisasi Aktual</th>
                                            <th class="text-end">Hasil Prediksi SVR</th>
                                            <th class="text-end">Nilai Error</th>
                                            <th class="text-end">Persentase Error</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($predictions as $index => $pred)
                                            <tr>
                                                <td>{{ $predictions->firstItem() + $index }}</td>
                                                <td>{{ Carbon\Carbon::parse($pred->tanggal)->translatedFormat('d F Y') }}</td>
                                                <td><span class="badge bg-light text-dark border">{{ $pred->rayon_name }}</span></td>
                                                <td class="text-end fw-semibold">Rp {{ number_format($pred->actual_value, 0, ',', '.') }}</td>
                                                <td class="text-end fw-bold text-primary-custom">Rp {{ number_format($pred->predicted_value, 0, ',', '.') }}</td>
                                                <td class="text-end text-danger">Rp {{ number_format($pred->error_value, 0, ',', '.') }}</td>
                                                <td class="text-end fw-semibold">{{ number_format($pred->percentage_error, 2, ',', '.') }}%</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-secondary">Tidak ada data prediksi yang cocok dengan filter.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Detailed Rayon Analysis Box -->
                        <div class="mt-4 p-3 bg-light rounded-3 border-start border-4 border-success shadow-sm">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-grid-3x3-gap-fill text-success me-1"></i>Analisis Akurasi Prediksi Per Rayon</h6>
                            <div class="row g-3 mt-1 text-sm text-secondary">
                                <div class="col-md-4 border-end border-light-subtle">
                                    <span class="text-xs text-uppercase fw-semibold d-block text-secondary mb-1">Rayon Paling Presisi (Lowest Error)</span>
                                    @if($bestRayon)
                                        <div class="fw-bold text-success fs-6 mb-1">{{ $bestRayon->rayon_name }}</div>
                                        <span class="d-block small">Rata-rata MAPE: <strong>{{ number_format($bestRayon->avg_mape, 2, ',', '.') }}%</strong></span>
                                        <span class="d-block text-xs text-muted">Akurasi peramalan di wilayah ini dinilai sangat andal.</span>
                                    @else
                                        <span class="text-muted small">Data tidak tersedia</span>
                                    @endif
                                </div>
                                <div class="col-md-4 border-end border-light-subtle">
                                    <span class="text-xs text-uppercase fw-semibold d-block text-secondary mb-1">Rayon dengan Deviasi Terbesar</span>
                                    @if($worstRayon)
                                        <div class="fw-bold text-danger fs-6 mb-1">{{ $worstRayon->rayon_name }}</div>
                                        <span class="d-block small">Rata-rata MAPE: <strong>{{ number_format($worstRayon->avg_mape, 2, ',', '.') }}%</strong></span>
                                        <span class="d-block text-xs text-muted">Deviasi dipengaruhi fluktuasi transaksi harian yang kurang stabil.</span>
                                    @else
                                        <span class="text-muted small">Data tidak tersedia</span>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <span class="text-xs text-uppercase fw-semibold d-block text-secondary mb-1">Metrik Deviasi Nominal</span>
                                    <div class="fw-bold text-dark fs-6 mb-1">Rp {{ number_format($avgDailyDeviation, 0, ',', '.') }} / hari</div>
                                    <span class="d-block small">Rata-rata kesalahan absolut harian.</span>
                                    <span class="d-block text-xs text-muted">Digunakan sebagai acuan toleransi deviasi setoran juru parkir di lapangan.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination container -->
                        @if($predictions->hasPages())
                            <div class="pagination-container mt-4 d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                <div class="text-secondary small">
                                    Menampilkan {{ $predictions->firstItem() ?? 0 }} - {{ $predictions->lastItem() ?? 0 }} dari {{ $predictions->total() }} data
                                </div>
                                <div>
                                    {!! $predictions->links('components.pagination') !!}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Bawah -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2 mb-4">
            <div>
                <button class="btn btn-outline-secondary px-4 py-2 rounded-3 fw-semibold text-sm me-2" onclick="goToStep(3)">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Eksekusi Model
                </button>
                <a href="{{ route('operator.prediksi.index') }}" class="btn btn-border"><i class="bi bi-arrow-clockwise"></i> Refresh Hasil</a>
                
                <button class="btn btn-outline-danger px-4 py-2 rounded-3 fw-semibold text-sm ms-2" onclick="confirmReset()">
                    <i class="bi bi-trash-fill me-1"></i> Reset Model
                </button>
                
                <form id="resetModelForm" method="POST" action="{{ route('operator.prediksi.reset') }}" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div> <!-- Close step-content-4 -->
    @endif

</div>

<!-- scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const hasLastRun = @json(isset($lastRun) && $lastRun->status == 'success');
    let activeStep = 1;

    window.confirmReset = function() {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Semua riwayat pelatihan model dan hasil prediksi SVR akan dihapus secara permanen dari database!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Reset Model!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('resetModelForm').submit();
            }
        });
    };

    window.goToStep = function(stepNum) {
        if (stepNum === 4 && !hasLastRun) {
            Swal.fire({
                title: 'Prediksi Belum Dijalankan!',
                text: 'Silakan jalankan proses Generate Prediksi SVR terlebih dahulu pada Langkah 3.',
                icon: 'warning',
                confirmButtonColor: '#005BAA',
                confirmButtonText: 'Tutup'
            });
            return;
        }

        // Update stepper UI
        for (let i = 1; i <= 4; i++) {
            const stepperItem = document.getElementById(`stepper-item-${i}`);
            if (stepperItem) {
                stepperItem.classList.remove('active', 'completed');
                if (i < stepNum) {
                    stepperItem.classList.add('completed');
                } else if (i === stepNum) {
                    stepperItem.classList.add('active');
                }
            }

            const stepperLine = document.getElementById(`stepper-line-${i}`);
            if (stepperLine) {
                stepperLine.classList.remove('completed');
                if (i < stepNum) {
                    stepperLine.classList.add('completed');
                }
            }

            const contentSection = document.getElementById(`step-content-${i}`);
            if (contentSection) {
                contentSection.classList.add('d-none');
            }
        }

        // Show active content
        const activeContent = document.getElementById(`step-content-${stepNum}`);
        if (activeContent) {
            activeContent.classList.remove('d-none');
        }
        
        activeStep = stepNum;

        if (stepNum === 4) {
            setTimeout(function() {
                const target = document.getElementById('step-content-4');
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 150);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        // Initialize step from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const activeStepParam = parseInt(urlParams.get('active_step'));
        if (activeStepParam === 4 && hasLastRun) {
            window.goToStep(4);
        } else {
            window.goToStep(1);
        }

        const btnJalankanSvrProses = document.getElementById('btnJalankanSvrProses');
        
        // Progress step management
        function setStepStatus(stepNum, status) {
            const stepElement = document.getElementById(`step-${stepNum}`);
            if (!stepElement) return;
            const iconSpan = stepElement.querySelector('.step-icon');
            
            // Remove previous classes
            stepElement.classList.remove('active', 'success-step', 'failed-step');
            
            if (status === 'pending') {
                iconSpan.innerHTML = '<i class="bi bi-circle"></i>';
                iconSpan.className = 'step-icon me-2 text-muted';
            } else if (status === 'processing') {
                iconSpan.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" style="width:12px; height:12px; border-width: 1.5px;" role="status"></div>';
                iconSpan.className = 'step-icon me-2';
                stepElement.classList.add('active');
            } else if (status === 'success') {
                iconSpan.innerHTML = '<i class="bi bi-check-circle-fill text-success" style="font-size: 14px;"></i>';
                iconSpan.className = 'step-icon me-2';
                stepElement.classList.add('success-step');
            } else if (status === 'failed') {
                iconSpan.innerHTML = '<i class="bi bi-x-circle-fill text-danger" style="font-size: 14px;"></i>';
                iconSpan.className = 'step-icon me-2';
                stepElement.classList.add('failed-step');
            }
        }
        
        let apiFinished = false;
        let apiError = null;
        let apiData = null;
        let currentStep = 1;
        let stepTimeout = null;
        
        function runStepSequence() {
            if (apiError) {
                // Jika API gagal, tandai langkah aktif dan sisa langkah sebagai gagal
                for (let i = currentStep; i <= 7; i++) {
                    setStepStatus(i, 'failed');
                }
                setTimeout(() => {
                    if (btnJalankanSvrProses) {
                        btnJalankanSvrProses.disabled = false;
                        btnJalankanSvrProses.innerHTML = '<i class="bi bi-play-fill me-1"></i> Generate Prediksi SVR';
                    }
                    
                    Swal.fire({
                        title: 'Gagal!',
                        text: apiError.message || 'Proses Generate SVR gagal. Silakan periksa layanan Python API atau kelengkapan dataset.',
                        icon: 'error',
                        confirmButtonColor: '#DC2626',
                        confirmButtonText: 'Tutup'
                    });
                }, 1200);
                return;
            }
            
            if (currentStep < 7) {
                // Sukses pada langkah saat ini
                setStepStatus(currentStep, 'success');
                // Pindah ke langkah berikutnya
                currentStep++;
                // Set langkah berikutnya sebagai sedang diproses
                setStepStatus(currentStep, 'processing');
                
                // Jalankan langkah berikutnya setelah 400ms
                stepTimeout = setTimeout(runStepSequence, 400);
            } else {
                // Kita berada di langkah terakhir (7. Prediksi Pendapatan)
                // Tunggu respons API selesai
                if (apiFinished) {
                    setStepStatus(7, 'success');
                    setTimeout(() => {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: (apiData && apiData.message) || 'Model SVR berhasil dijalankan.',
                            icon: 'success',
                            confirmButtonColor: '#005BAA',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            window.location.href = "{{ route('operator.prediksi.index') }}?active_step=4";
                        });
                    }, 800);
                } else {
                    // Jika API belum selesai, cek kembali dalam 200ms
                    stepTimeout = setTimeout(runStepSequence, 200);
                }
            }
        }
        
        if (btnJalankanSvrProses) {
            btnJalankanSvrProses.addEventListener('click', function() {
                Swal.fire({
                    title: 'Jalankan Pelatihan Model SVR?',
                    text: "Sistem akan memulai proses preprocessing, training, dan peramalan menggunakan algoritma SVR. Harap tunggu hingga selesai.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#005BAA',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Ya, Jalankan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Disable button and show spinner
                        btnJalankanSvrProses.disabled = true;
                        btnJalankanSvrProses.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses SVR...';
                        
                        // Reset state
                        apiFinished = false;
                        apiError = null;
                        apiData = null;
                        currentStep = 1;
                        if (stepTimeout) clearTimeout(stepTimeout);
                        
                        // Reset semua langkah ke pending
                        for (let i = 1; i <= 7; i++) {
                            setStepStatus(i, 'pending');
                        }
                        
                        // Mulai langkah pertama
                        setStepStatus(1, 'processing');
                        stepTimeout = setTimeout(runStepSequence, 400);
                        
                        // AJAX Request ke backend laravel
                        fetch("{{ route('operator.prediksi.run-svr') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({})
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => { throw err; });
                            }
                            return response.json();
                        })
                        .then(data => {
                            apiFinished = true;
                            apiData = data;
                        })
                        .catch(error => {
                            apiFinished = true;
                            apiError = error;
                        });
                    }
                });
            });
        }
        
        // 2. Chart.js Implementation (Hanya jika data chart tersedia)
        @if($lastRun && $chartData->count() > 0)
            const ctx = document.getElementById('svrChart').getContext('2d');
            
            // Gradient Fills
            const gradientActual = ctx.createLinearGradient(0, 0, 0, 380);
            gradientActual.addColorStop(0, 'rgba(0, 91, 170, 0.12)');
            gradientActual.addColorStop(1, 'rgba(0, 91, 170, 0.0)');

            const gradientPredict = ctx.createLinearGradient(0, 0, 0, 380);
            gradientPredict.addColorStop(0, 'rgba(244, 197, 66, 0.08)');
            gradientPredict.addColorStop(1, 'rgba(244, 197, 66, 0.0)');
            
            const labels = @json($chartData->pluck('tanggal')->map(fn($t) => Carbon\Carbon::parse($t)->format('d M Y'))->toArray());
            const actualData = @json($chartData->pluck('actual_value')->map(fn($v) => (double)$v)->toArray());
            const predictedData = @json($chartData->pluck('predicted_value')->map(fn($v) => (double)$v)->toArray());
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Pendapatan Aktual',
                            data: actualData,
                            borderColor: '#005BAA',
                            borderWidth: 2,
                            backgroundColor: gradientActual,
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: '#005BAA',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 1,
                            pointRadius: 2.5,
                            pointHoverRadius: 4
                        },
                        {
                            label: 'Pendapatan Prediksi SVR',
                            data: predictedData,
                            borderColor: '#F4C542',
                            borderWidth: 2,
                            backgroundColor: gradientPredict,
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: '#F4C542',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 1,
                            pointRadius: 2.5,
                            pointHoverRadius: 4,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                boxWidth: 10,
                                padding: 12,
                                font: {
                                    family: 'Inter',
                                    size: 11,
                                    weight: '500'
                                },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            padding: 10,
                            backgroundColor: '#1f2937',
                            titleFont: { family: 'Inter', size: 11, weight: 'bold' },
                            bodyFont: { family: 'Inter', size: 11 },
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    let val = context.raw;
                                    return ' ' + label + ': Rp ' + new Intl.NumberFormat('id-ID').format(val);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            grid: {
                                borderDash: [5, 5],
                                color: '#e2e8f0'
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(value);
                                },
                                font: {
                                    family: 'Inter',
                                    size: 10
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'Inter',
                                    size: 9.5
                                },
                                maxRotation: 45,
                                autoSkip: true,
                                maxTicksLimit: 12
                            }
                        }
                    }
                }
            });
        @endif
        
    });
</script>
@endsection
