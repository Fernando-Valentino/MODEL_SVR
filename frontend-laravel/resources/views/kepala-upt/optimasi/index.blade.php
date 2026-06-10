@extends('layouts.app')

@section('title', 'Optimasi Parameter')
@section('subtitle', 'Halaman ini digunakan untuk melihat hasil perbandingan optimasi parameter model prediksi.')

@section('content')
<div class="container-fluid p-0">
    
    <!-- Info Box: MODE LIHAT -->
    <div class="alert alert-secondary d-flex align-items-center py-2 px-3 mb-4 rounded-3 border-secondary-subtle" role="alert">
        <i class="bi bi-eye-fill me-2 fs-5 text-dark"></i>
        <div class="small">
            <span class="fw-bold text-dark">MODE LIHAT:</span> Pengguna hanya dapat memantau hasil optimasi parameter tanpa opsi untuk menjalankan ulang tuning model.
        </div>
    </div>

    <!-- Result Cards (Grid Search vs GWO) -->
    <div class="row g-4 mb-4">
        <!-- Grid Search Result Card -->
        <div class="col-md-6">
            <div class="card h-100 border-start border-4 border-start-secondary">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-grid-3x3 me-2"></i>Hasil Tuning Grid Search</h5>
                    
                    <div class="row g-2 mb-2">
                        <div class="col-6"><span class="small text-secondary">Best C:</span></div>
                        <div class="col-6"><span class="small fw-semibold text-dark">{{ $grid_best['c'] }}</span></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><span class="small text-secondary">Epsilon (&epsilon;):</span></div>
                        <div class="col-6"><span class="small fw-semibold text-dark">{{ $grid_best['epsilon'] }}</span></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><span class="small text-secondary">Gamma (&gamma;):</span></div>
                        <div class="col-6"><span class="small fw-semibold text-dark">{{ $grid_best['gamma'] }}</span></div>
                    </div>
                    <div class="row g-2 pt-2 border-top mt-2">
                        <div class="col-6"><span class="small text-secondary fw-bold">Akurasi Model:</span></div>
                        <div class="col-6"><span class="small fw-bold text-success">{{ $grid_best['accuracy'] }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GWO Result Card -->
        <div class="col-md-6">
            <div class="card h-100 border-start border-4 border-start-dark">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-activity me-2"></i>Hasil Tuning GWO (Global Optimal)</h5>
                    
                    <div class="row g-2 mb-2">
                        <div class="col-6"><span class="small text-secondary">Best C:</span></div>
                        <div class="col-6"><span class="small fw-semibold text-dark">{{ $gwo_best['c'] }}</span></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><span class="small text-secondary">Epsilon (&epsilon;):</span></div>
                        <div class="col-6"><span class="small fw-semibold text-dark">{{ $gwo_best['epsilon'] }}</span></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><span class="small text-secondary">Gamma (&gamma;):</span></div>
                        <div class="col-6"><span class="small fw-semibold text-dark">{{ $gwo_best['gamma'] }}</span></div>
                    </div>
                    <div class="row g-2 pt-2 border-top mt-2">
                        <div class="col-6"><span class="small text-secondary fw-bold">Akurasi Model:</span></div>
                        <div class="col-6"><span class="small fw-bold text-success">{{ $gwo_best['accuracy'] }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparison Table -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Tabel Hasil Perbandingan Optimasi</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Metode</th>
                            <th>C</th>
                            <th>Epsilon (&epsilon;)</th>
                            <th>Gamma (&gamma;)</th>
                            <th>MAE</th>
                            <th>RMSE</th>
                            <th>MAPE</th>
                            <th>R² Score</th>
                            <th>Akurasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparisons as $comp)
                            <tr>
                                <td style="font-weight: 600;">{{ $comp['metode'] }}</td>
                                <td>{{ $comp['c'] }}</td>
                                <td>{{ $comp['epsilon'] }}</td>
                                <td>{{ $comp['gamma'] }}</td>
                                <td>{{ $comp['mae'] }}</td>
                                <td>{{ $comp['rmse'] }}</td>
                                <td>{{ $comp['mape'] }}</td>
                                <td>{{ $comp['r2'] }}</td>
                                <td style="font-weight: 600;" class="text-success">{{ $comp['akurasi'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Grafik Section -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Grafik Perbandingan Performa</h5>
            <div class="chart-placeholder" style="height: 260px;">
                <span class="fs-5 fw-medium"><i class="bi bi-bar-chart-line fs-3 d-block text-center mb-2"></i>[ Grafik Perbandingan Akurasi (MAPE) ]</span>
                <span class="text-secondary small">Visualisasi komparasi tingkat error model (SVR Default vs Grid Search vs GWO)</span>
            </div>
        </div>
    </div>

</div>
@endsection
