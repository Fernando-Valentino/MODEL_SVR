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
    <div class="card mb-4 bg-white">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-bar-chart-line me-2 text-dark"></i>Grafik Perbandingan Performa Model</h5>
            <div style="position: relative; height: 320px;">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const canvasEl = document.getElementById('performanceChart');
        if (typeof Chart !== 'undefined' && canvasEl) {
            const ctx = canvasEl.getContext('2d');
            
            // Performance metric constants
            const mapeSvrDefault = @json((double)($chartMetrics['mape_default'] ?? 8.42));
            const r2SvrDefault   = @json((double)($chartMetrics['r2_default']   ?? 0.84));
            const mapeGridSearch = @json((double)($chartMetrics['mape_gs']      ?? 5.89));
            const r2GridSearch   = @json((double)($chartMetrics['r2_gs']        ?? 0.91));
            const mapeGwo        = @json((double)($chartMetrics['mape_gwo']     ?? 4.74));
            const r2Gwo          = @json((double)($chartMetrics['r2_gwo']       ?? 0.94));

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['SVR Standar (Default)', 'SVR + Grid Search', 'SVR + GWO (Grey Wolf)'],
                    datasets: [
                        {
                            label: 'MAPE (%) (Semakin Kecil Semakin Baik)',
                            data: [mapeSvrDefault, mapeGridSearch, mapeGwo],
                            backgroundColor: [
                                'rgba(220, 38, 38, 0.75)',
                                'rgba(245, 158, 11, 0.75)',
                                'rgba(16, 185, 129, 0.75)'
                            ],
                            borderColor: ['rgb(220, 38, 38)', 'rgb(245, 158, 11)', 'rgb(16, 185, 129)'],
                            borderWidth: 1.5,
                            yAxisID: 'y'
                        },
                        {
                            label: 'R² Score (Semakin Besar Semakin Baik)',
                            data: [r2SvrDefault, r2GridSearch, r2Gwo],
                            backgroundColor: 'rgba(0, 91, 170, 0.15)',
                            borderColor: '#005BAA',
                            borderWidth: 1.5,
                            type: 'line',
                            tension: 0.2,
                            pointBackgroundColor: '#005BAA',
                            pointRadius: 4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { font: { family: 'Inter', size: 11 } } },
                        tooltip: { padding: 10, backgroundColor: '#1f2937', titleFont: { family: 'Inter', size: 11, weight: 'bold' }, bodyFont: { family: 'Inter', size: 11 } }
                    },
                    scales: {
                        y: {
                            type: 'linear', display: true, position: 'left',
                            title: { display: true, text: 'MAPE (%)', font: { family: 'Inter', size: 11, weight: 'bold' } },
                            grid: { borderDash: [5, 5], color: '#e2e8f0' },
                            ticks: { callback: v => v + '%', font: { family: 'Inter', size: 10 } }
                        },
                        y1: {
                            type: 'linear', display: true, position: 'right',
                            title: { display: true, text: 'R² Score', font: { family: 'Inter', size: 11, weight: 'bold' } },
                            grid: { drawOnChartArea: false },
                            min: 0, max: 1.0,
                            ticks: { font: { family: 'Inter', size: 10 } }
                        },
                        x: { ticks: { font: { family: 'Inter', size: 11 } } }
                    }
                }
            });
        }
    });
</script>
@endsection
