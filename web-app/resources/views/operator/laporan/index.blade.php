@extends('layouts.app')

@section('title', 'Laporan Prediksi')
@section('subtitle', 'Halaman ini digunakan untuk melihat dan mengekspor laporan hasil prediksi pendapatan retribusi parkir.')

@section('content')
<div class="container-fluid p-0">

    <!-- Filter & Export Toolbar -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title border-0 pb-0 mb-3"><i class="bi bi-funnel me-2"></i>Filter & Ekspor Laporan</h5>
            
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="2026-06-01" />
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tanggal Akhir</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="2026-06-07" />
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Rayon</label>
                    <select name="rayon_id" class="form-select form-select-sm">
                        <option value="0">Semua Rayon</option>
                        <option value="1">Rayon I</option>
                        <option value="2">Rayon II</option>
                        <option value="3">Rayon III</option>
                        <option value="4">Rayon IV</option>
                        <option value="5">Rayon V</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Jenis Laporan</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="harian">Laporan Harian (Rinci)</option>
                        <option value="bulanan">Laporan Bulanan (Rekap)</option>
                    </select>
                </div>
                
                <div class="col-12 mt-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-dark btn-sm px-4" onclick="alert('Laporan disaring berdasarkan tanggal yang diinput.')"><i class="bi bi-search me-1"></i> Tampilkan</button>
                    <div class="d-flex gap-2">
                        <!-- Future Integration: exports are handled by PDF/Excel Service (ReportService.php) -->
                        <a href="{{ route('operator.laporan.export-pdf') }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> Export PDF</a>
                        <a href="{{ route('operator.laporan.export-excel') }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Export Excel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Periode Laporan</span>
                    <div class="fw-bold text-dark" style="font-size: 14px;">{{ $summary['periode'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Total Baris Data</span>
                    <div class="h4 fw-bold mb-0 text-dark">{{ $summary['total_data'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Total Realisasi Aktual</span>
                    <div class="h4 fw-bold mb-0 text-dark">{{ $summary['total_aktual'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Total Prediksi SVR-GWO</span>
                    <div class="h4 fw-bold mb-0 text-dark">{{ $summary['total_prediksi'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart card -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Grafik Tren Pendapatan dan Prediksi</h5>
            <div class="chart-placeholder" style="height: 280px;">
                <span class="fs-5 fw-medium"><i class="bi bi-graph-up fs-3 d-block text-center mb-2"></i>[ Grafik Tren Laporan ]</span>
                <span class="text-secondary small">Menampilkan visualisasi perbandingan realisasi vs prediksi SVR untuk dicetak ke PDF/Excel</span>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title border-0 pb-0 mb-3">Tabel Laporan Hasil Prediksi</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Rayon</th>
                            <th style="text-align: right;">Aktual (Rp)</th>
                            <th style="text-align: right;">Prediksi (Rp)</th>
                            <th style="text-align: right;">Error (Rp)</th>
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $rep)
                            <tr>
                                <td>{{ $rep['tanggal'] }}</td>
                                <td>{{ $rep['rayon'] }}</td>
                                <td style="text-align: right;">Rp {{ number_format($rep['aktual'], 0, ',', '.') }}</td>
                                <td style="text-align: right; font-weight: 600;">Rp {{ number_format($rep['prediksi'], 0, ',', '.') }}</td>
                                <td style="text-align: right; color: {{ $rep['error'] >= 0 ? '#10b981' : '#ef4444' }};">
                                    {{ $rep['error'] >= 0 ? '+' : '' }}Rp {{ number_format($rep['error'], 0, ',', '.') }}
                                </td>
                                <td style="text-align: center;">
                                    <button class="btn-action" title="Detail" onclick="alert('Detail data tanggal {{ $rep['tanggal'] }}')"><i class="bi bi-eye"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Placeholder -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <div class="text-secondary small">Menampilkan 1 - 7 dari 7 data</div>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled"><span class="page-link">«</span></li>
                        <li class="page-item active"><span class="page-link bg-dark border-dark text-white">1</span></li>
                        <li class="page-item disabled"><span class="page-link">»</span></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Evaluasi Model Cards -->
    <div class="row g-4">
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

</div>
@endsection
