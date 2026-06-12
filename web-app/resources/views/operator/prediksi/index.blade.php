@extends('layouts.app')

@section('title', 'Kelola Model Prediksi')
@section('subtitle', 'Halaman ini digunakan untuk menjalankan proses prediksi pendapatan retribusi parkir menggunakan model Support Vector Regression.')

@section('content')
<div class="container-fluid p-0">
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('operator.prediksi.run-svr') }}">
        @csrf
        
        <div class="row g-4 mb-4">
            <!-- Parameter Model SVR Card -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-sliders me-2"></i>Parameter Model SVR</h5>
                        
                        <div class="mb-3">
                            <label for="param_c" class="form-label small fw-semibold">C (Regularization Parameter)</label>
                            <input type="number" step="any" id="param_c" name="c" class="form-control" value="{{ $params['c'] }}" required>
                            <div class="form-text text-muted" style="font-size: 11px;">Mengontrol trade-off antara margin error dan penalty klasifikasi salah.</div>
                        </div>

                        <div class="mb-3">
                            <label for="param_epsilon" class="form-label small fw-semibold">Epsilon (&epsilon;)</label>
                            <input type="number" step="any" id="param_epsilon" name="epsilon" class="form-control" value="{{ $params['epsilon'] }}" required>
                            <div class="form-text text-muted" style="font-size: 11px;">Menentukan ambang batas lebar pipa di mana error diabaikan.</div>
                        </div>

                        <div class="mb-3">
                            <label for="param_gamma" class="form-label small fw-semibold">Gamma (&gamma;)</label>
                            <input type="number" step="any" id="param_gamma" name="gamma" class="form-control" value="{{ $params['gamma'] }}" required>
                            <div class="form-text text-muted" style="font-size: 11px;">Mengatur jangkauan pengaruh dari satu baris training data.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Periode Data Card -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title"><i class="bi bi-calendar-range me-2"></i>Filter Periode Data</h5>
                            
                            <div class="mb-3">
                                <label for="tanggal_mulai" class="form-label small fw-semibold">Tanggal Mulai</label>
                                <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control" value="{{ $params['tanggal_mulai'] }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="tanggal_akhir" class="form-label small fw-semibold">Tanggal Akhir</label>
                                <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="form-control" value="{{ $params['tanggal_akhir'] }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="rayon_id" class="form-label small fw-semibold">Rayon Target</label>
                                <select id="rayon_id" name="rayon_id" class="form-select">
                                    <option value="0" {{ $params['rayon'] == 0 ? 'selected' : '' }}>Semua Rayon (Total)</option>
                                    <option value="1" {{ $params['rayon'] == 1 ? 'selected' : '' }}>Rayon I</option>
                                    <option value="2" {{ $params['rayon'] == 2 ? 'selected' : '' }}>Rayon II</option>
                                    <option value="3" {{ $params['rayon'] == 3 ? 'selected' : '' }}>Rayon III</option>
                                    <option value="4" {{ $params['rayon'] == 4 ? 'selected' : '' }}>Rayon IV</option>
                                    <option value="5" {{ $params['rayon'] == 5 ? 'selected' : '' }}>Rayon V</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action trigger to FastAPI -->
                        <div class="mt-3">
                            <!-- Future FastAPI Integration: POST request with parameters C, epsilon, gamma, tanggal_mulai, tanggal_akhir, rayon_id -->
                            <button type="submit" class="btn btn-dark w-100 py-2"><i class="bi bi-cpu-fill me-2"></i>Generate Prediksi SVR</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Evaluasi Model Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Mean Absolute Error (MAE)</span>
                    <h4 class="fw-bold mb-0">{{ $metrics['mae'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Root Mean Squared Error (RMSE)</span>
                    <h4 class="fw-bold mb-0">{{ $metrics['rmse'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Akurasi MAPE</span>
                    <h4 class="fw-bold mb-0 text-success">{{ $metrics['mape'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Coefficient of Determination (R²)</span>
                    <h4 class="fw-bold mb-0">{{ $metrics['r2'] }}</h4>
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
                    <div class="chart-placeholder" style="height: 360px;">
                        <span class="fs-5 fw-medium"><i class="bi bi-graph-up-arrow fs-3 d-block text-center mb-2"></i>[ Visualisasi Prediksi SVR ]</span>
                        <span class="text-secondary small">Grafik garis realisasi aktual (solid) vs hasil peramalan SVR (dashed)</span>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <span class="legend-color legend-actual"></span>
                                <span>Realisasi Aktual</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color legend-predicted"></span>
                                <span>Prediksi SVR</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table list -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Tabel Hasil Prediksi</h5>
                    <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Rayon</th>
                                    <th style="text-align: right;">Aktual (Rp)</th>
                                    <th style="text-align: right;">Prediksi (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($predictions as $pred)
                                    <tr>
                                        <td>{{ $pred['tanggal'] }}</td>
                                        <td>{{ $pred['rayon'] }}</td>
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
