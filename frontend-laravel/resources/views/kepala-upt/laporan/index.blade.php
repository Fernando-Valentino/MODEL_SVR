@extends('layouts.app')

@section('title', 'Laporan Prediksi Pendapatan Retribusi')
@section('subtitle', 'Halaman ini digunakan untuk melihat dan mengekspor laporan hasil prediksi sebagai bahan evaluasi UPT Parkir.')

@section('content')
<div class="container-fluid p-0">

    <!-- Filter & PDF Toolbar -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title border-0 pb-0 mb-3"><i class="bi bi-funnel me-2"></i>Filter & Cetak Laporan</h5>
            
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="2026-06-01" />
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Tanggal Akhir</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="2026-06-07" />
                </div>
                <div class="col-md-4">
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
                
                <div class="col-12 mt-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-dark btn-sm px-4" onclick="alert('Laporan disaring berdasarkan tanggal yang diinput.')"><i class="bi bi-search me-1"></i> Tampilkan</button>
                    <div class="d-flex gap-2">
                        <!-- Spatie Role rule: UPT has only export PDF access -->
                        <a href="{{ route('kepala-upt.laporan.export-pdf') }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> Export PDF Laporan</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body py-3">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 9px; letter-spacing: 0.5px;">Periode</span>
                    <div class="fw-bold text-dark small">{{ $summary['periode'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100 text-center">
                <div class="card-body py-3">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 9px; letter-spacing: 0.5px;">Total Data</span>
                    <div class="fw-bold text-dark h5 mb-0">{{ $summary['total_data'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100 text-center">
                <div class="card-body py-3">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 9px; letter-spacing: 0.5px;">Total Realisasi</span>
                    <div class="fw-bold text-dark h5 mb-0">{{ $summary['total_aktual'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body py-3">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 9px; letter-spacing: 0.5px;">Total Prediksi</span>
                    <div class="fw-bold text-dark h5 mb-0">{{ $summary['total_prediksi'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100 text-center bg-dark text-white">
                <div class="card-body py-3">
                    <span class="text-uppercase text-white-50 fw-semibold d-block mb-1" style="font-size: 9px; letter-spacing: 0.5px;">Akurasi MAPE</span>
                    <div class="fw-bold h5 mb-0">{{ $summary['mape'] }}</div>
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
                <span class="text-secondary small">Menampilkan visualisasi perbandingan realisasi vs prediksi SVR untuk dicetak ke PDF</span>
            </div>
        </div>
    </div>

    <!-- Table Card with Totals Footer -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title border-0 pb-0 mb-3">Tabel Laporan Harian</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 65px;">No</th>
                            <th>Tanggal</th>
                            <th>Rayon</th>
                            <th style="text-align: right;">Aktual (Rp)</th>
                            <th style="text-align: right;">Prediksi (Rp)</th>
                            <th style="text-align: right;">Error (Rp)</th>
                            <th style="text-align: right; width: 120px;">% Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $rep)
                            <tr>
                                <td>{{ $rep['no'] }}</td>
                                <td>{{ $rep['tanggal'] }}</td>
                                <td>{{ $rep['rayon'] }}</td>
                                <td style="text-align: right;">Rp {{ number_format($rep['aktual'], 0, ',', '.') }}</td>
                                <td style="text-align: right; font-weight: 600;">Rp {{ number_format($rep['prediksi'], 0, ',', '.') }}</td>
                                <td style="text-align: right; color: {{ $rep['error'] >= 0 ? '#10b981' : '#ef4444' }};">
                                    {{ $rep['error'] >= 0 ? '+' : '' }}Rp {{ number_format($rep['error'], 0, ',', '.') }}
                                </td>
                                <td style="text-align: right; font-weight: 500;">{{ $rep['pct_error'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold border-top-2">
                        <tr>
                            <td colspan="3">Total Periode Ini</td>
                            <td style="text-align: right;">{{ $total_period['aktual'] }}</td>
                            <td style="text-align: right;">{{ $total_period['prediksi'] }}</td>
                            <td style="text-align: right; color: #10b981;">+{{ $total_period['error'] }}</td>
                            <td style="text-align: right;">Rerata: {{ $total_period['pct_error'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
