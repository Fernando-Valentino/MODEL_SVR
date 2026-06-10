@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid p-0">
    <!-- 1. Metrics Grid (Bootstrap Row) -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-2" style="font-size: 11px; letter-spacing: 0.5px;">Total Pendapatan Harian</span>
                    <div class="h2 fw-bold text-dark mb-1">{{ $metrics['total_pendapatan_harian'] }}</div>
                    <span class="text-secondary small"><i class="bi bi-info-circle me-1"></i>Realisasi hari ini (5 Rayon)</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-2" style="font-size: 11px; letter-spacing: 0.5px;">Hasil Prediksi Terkini</span>
                    <div class="h2 fw-bold text-dark mb-1">{{ $metrics['hasil_prediksi_terkini'] }}</div>
                    <span class="text-secondary small"><i class="bi bi-graph-up me-1"></i>Proyeksi model SVR-GWO besok</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-2" style="font-size: 11px; letter-spacing: 0.5px;">Akurasi Model (MAPE)</span>
                    <div class="h2 fw-bold text-dark mb-1">{{ $metrics['akurasi_model'] }}</div>
                    <span class="text-secondary small"><i class="bi bi-check-circle me-1"></i>Pelatihan model terakhir</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Large Chart Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Grafik Tren Pendapatan Retribusi Parkir</h5>
            
            <div class="chart-placeholder">
                <span class="fs-5 fw-medium"><i class="bi bi-graph-up-arrow fs-3 d-block text-center mb-2"></i>[ Grafik Tren Realisasi vs Prediksi SVR-GWO ]</span>
                <span class="text-secondary small">Menampilkan fluktuasi pendapatan harian per rayon dan proyeksi model ke depan</span>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color legend-actual"></span>
                        <span>Realisasi Pendapatan</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color legend-predicted"></span>
                        <span>Prediksi Model SVR-GWO</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Latest Data Table -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Data Pendapatan Terkini</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 65px;">No</th>
                            <th>Tanggal</th>
                            <th>Rayon</th>
                            <th>Juru Parkir</th>
                            <th style="text-align: right;">Jumlah Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($incomes as $income)
                            <tr>
                                <td>{{ $income['no'] }}</td>
                                <td>{{ $income['tanggal'] }}</td>
                                <td>{{ $income['rayon'] }}</td>
                                <td>{{ $income['juru_parkir'] }}</td>
                                <td style="text-align: right; font-weight: 600;">{{ $income['jumlah'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
