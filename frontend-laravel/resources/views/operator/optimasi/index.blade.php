@extends('layouts.app')

@section('title', 'Optimasi Parameter')
@section('subtitle', 'Halaman ini digunakan untuk membandingkan hasil optimasi parameter SVR menggunakan Grid Search dan Grey Wolf Optimizer.')

@section('content')
<div class="container-fluid p-0">
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <!-- Grid Search Card -->
        <div class="col-md-6">
            <div class="card h-100">
                <form method="POST" action="{{ route('operator.optimasi.grid-search') }}">
                    @csrf
                    <div class="card-body d-flex flex-column justify-content-between" style="min-height: 400px;">
                        <div>
                            <h5 class="card-title"><i class="bi bi-grid-3x3 me-2"></i>Optimasi Grid Search</h5>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Rentang C</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="grid_c" class="form-control" value="[0.1, 1, 10, 100]" placeholder="Contoh: [0.1, 1, 10]">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Rentang Epsilon (&epsilon;)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="grid_epsilon" class="form-control" value="[0.001, 0.01, 0.1]" placeholder="Contoh: [0.001, 0.01]">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Rentang Gamma (&gamma;)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="grid_gamma" class="form-control" value="[0.001, 0.01, 0.1]" placeholder="Contoh: [0.001, 0.01]">
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-secondary py-2 px-3 mb-0" style="font-size: 11px;">
                                <i class="bi bi-info-circle me-1"></i> Menggunakan 5-Fold Cross Validation untuk penentuan parameter terbaik.
                            </div>
                        </div>

                        <!-- Future FastAPI Integration: POST to /optimize/grid-search with arrays -->
                        <div class="mt-4">
                            <button type="submit" class="btn btn-dark w-100 py-2"><i class="bi bi-play-fill me-1"></i>Jalankan Grid Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- GWO Card -->
        <div class="col-md-6">
            <div class="card h-100">
                <form method="POST" action="{{ route('operator.optimasi.gwo') }}">
                    @csrf
                    <div class="card-body d-flex flex-column justify-content-between" style="min-height: 400px;">
                        <div>
                            <h5 class="card-title"><i class="bi bi-activity me-2"></i>Optimasi GWO (Grey Wolf Optimizer)</h5>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="gwo_wolves" class="form-label small fw-semibold">Jumlah Serigala (Wolves)</label>
                                    <input type="number" id="gwo_wolves" name="wolves" class="form-control form-control-sm" value="10" min="5" max="50">
                                </div>
                                <div class="col-6">
                                    <label for="gwo_iterations" class="form-label small fw-semibold">Maksimal Iterasi</label>
                                    <input type="number" id="gwo_iterations" name="iterations" class="form-control form-control-sm" value="50" min="10" max="200">
                                </div>
                            </div>

                            <div class="mb-3">
                                <span class="d-block small fw-bold mb-2">Search Space Bounds (Rentang Kontinu)</span>
                                
                                <div class="row g-2 mb-2">
                                    <div class="col-4"><label class="small text-muted mb-1">C (Min/Max)</label></div>
                                    <div class="col-4"><input type="number" step="any" name="c_min" class="form-control form-control-sm" value="0.1" placeholder="Min"></div>
                                    <div class="col-4"><input type="number" step="any" name="c_max" class="form-control form-control-sm" value="100.0" placeholder="Max"></div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-4"><label class="small text-muted mb-1">Epsilon (Min/Max)</label></div>
                                    <div class="col-4"><input type="number" step="any" name="epsilon_min" class="form-control form-control-sm" value="0.0001" placeholder="Min"></div>
                                    <div class="col-4"><input type="number" step="any" name="epsilon_max" class="form-control form-control-sm" value="0.1" placeholder="Max"></div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-4"><label class="small text-muted mb-1">Gamma (Min/Max)</label></div>
                                    <div class="col-4"><input type="number" step="any" name="gamma_min" class="form-control form-control-sm" value="0.0001" placeholder="Min"></div>
                                    <div class="col-4"><input type="number" step="any" name="gamma_max" class="form-control form-control-sm" value="1.0" placeholder="Max"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Future FastAPI Integration: POST to /optimize/gwo with search bounds and GWO parameters -->
                        <div class="mt-4">
                            <button type="submit" class="btn btn-dark w-100 py-2"><i class="bi bi-play-fill me-1"></i>Jalankan GWO</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hasil Optimasi Parameter Table -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Hasil Optimasi Parameter</h5>
            
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
                                <td style="font-weight: 600;" class="text-success">{{ $comp['mape'] }}</td>
                                <td>{{ $comp['r2'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Grafik Perbandingan Performa -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Grafik Perbandingan Performa Model</h5>
            <div class="chart-placeholder" style="height: 280px;">
                <span class="fs-5 fw-medium"><i class="bi bi-bar-chart-line fs-3 d-block text-center mb-2"></i>[ Grafik Perbandingan Akurasi (MAPE) ]</span>
                <span class="text-secondary small">Visualisasi komparasi tingkat error model (SVR Default vs Grid Search vs GWO)</span>
            </div>
        </div>
    </div>

</div>
@endsection
