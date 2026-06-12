@extends('layouts.app')

@section('title', 'Kelola Model Prediksi')
@section('subtitle', 'Halaman ini digunakan untuk melihat hasil prediksi pendapatan retribusi parkir yang telah diproses oleh sistem.')

@section('content')
<div class="container-fluid p-0">
    
    <!-- Info Box: MODE LIHAT -->
    <div class="alert alert-secondary d-flex align-items-center py-2 px-3 mb-4 rounded-3 border-secondary-subtle" role="alert">
        <i class="bi bi-eye-fill me-2 fs-5 text-dark"></i>
        <div class="small">
            <span class="fw-bold text-dark">MODE LIHAT:</span> Pengguna tidak dapat melakukan perubahan data parameter SVR atau memicu retraining model.
        </div>
    </div>

    <!-- Parameter Model Cards -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title border-0 pb-0 mb-3"><i class="bi bi-gear-fill me-2"></i>Konfigurasi Parameter Model Aktif</h5>
            
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 text-center border">
                        <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Best C (Penalty)</span>
                        <div class="fw-bold text-dark fs-5">{{ $best_params['c'] }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 text-center border">
                        <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Epsilon (&epsilon;)</span>
                        <div class="fw-bold text-dark fs-5">{{ $best_params['epsilon'] }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 text-center border">
                        <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Gamma (&gamma;)</span>
                        <div class="fw-bold text-dark fs-5">{{ $best_params['gamma'] }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-dark rounded-3 text-center text-white">
                        <span class="text-uppercase text-white-50 fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Metode Terbaik</span>
                        <div class="fw-bold fs-6">{{ $best_params['metode_terbaik'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Evaluasi Model Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 9px; letter-spacing: 0.5px;">MAE</span>
                    <h5 class="fw-bold mb-0">{{ $metrics['mae'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 9px; letter-spacing: 0.5px;">RMSE</span>
                    <h5 class="fw-bold mb-0">{{ $metrics['rmse'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 9px; letter-spacing: 0.5px;">MAPE</span>
                    <h5 class="fw-bold mb-0 text-success">{{ $metrics['mape'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 9px; letter-spacing: 0.5px;">R² Score</span>
                    <h5 class="fw-bold mb-0">{{ $metrics['r2'] }}</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik & Hasil Table Section -->
    <div class="row g-4">
        <!-- Chart placeholder -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Grafik Aktual vs Prediksi</h5>
                    <div class="chart-placeholder" style="height: 320px;">
                        <span class="fs-5 fw-medium"><i class="bi bi-graph-up-arrow fs-3 d-block text-center mb-2"></i>[ Visualisasi Tren Prediksi ]</span>
                        <span class="text-secondary small">Menampilkan perbandingan tren pendapatan realisasi vs peramalan SVR</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table list (5 Months) -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Data Prediksi (5 Bulan Terakhir)</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th style="text-align: right;">Aktual (Rp)</th>
                                    <th style="text-align: right;">Prediksi (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($predictions_monthly as $pred)
                                    <tr>
                                        <td style="font-weight: 500;">{{ $pred['bulan'] }}</td>
                                        <td style="text-align: right;">{{ $pred['aktual'] }}</td>
                                        <td style="text-align: right; font-weight: 600;">{{ $pred['prediksi'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
