@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid p-0">
    <style>
        .dashboard-card-hover {
            transition: all 0.2s ease-in-out;
            border: 1px solid var(--border) !important;
        }
        .dashboard-card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 91, 170, 0.06) !important;
        }
        .quick-action-btn {
            transition: all 0.2s ease;
            border: 1px solid var(--border) !important;
            border-radius: 8px;
            background: #ffffff;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px 14px;
        }
        .quick-action-btn:hover {
            background-color: var(--primary-blue-light) !important;
            border-color: var(--primary-blue) !important;
            color: var(--primary-blue) !important;
            transform: translateX(4px);
        }
        .icon-box {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            flex-shrink: 0;
        }
    </style>

    <!-- Welcome Hero Banner -->
    <div class="card border-0 text-white mb-4 shadow-sm" style="background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--primary-blue) 100%); border-radius: 12px; padding: 20px 24px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1" style="font-size: 18px;"><i class="bi bi-speedometer2 me-2"></i>Dashboard Operator</h4>
                <p class="mb-0 opacity-75 small" style="font-size: 12px;">Selamat datang kembali! Pantau tren realisasi pendapatan, latih model prediksi SVR-GWO, dan kelola data retribusi parkir harian.</p>
            </div>
            <div>
                <span class="badge bg-white text-primary px-3 py-2 fw-semibold shadow-sm" style="font-size: 11px; color: var(--primary-blue) !important;">
                    <i class="bi bi-person-circle me-1"></i> Operator UPT Parkir
                </span>
            </div>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="row g-3 mb-4">
        <!-- Metric 1: Realisasi Harian Terkini -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 mb-0 dashboard-card-hover bg-white" style="padding: 16px 20px; border-radius: 12px;">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-success-subtle text-success me-3">
                        <i class="bi bi-cash-coin fs-5"></i>
                    </div>
                    <div>
                        <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Realisasi Terkini</span>
                        <h4 class="fw-bold text-dark mb-0" style="font-size: 18px;">{{ $metrics['total_pendapatan_harian'] }}</h4>
                        <span class="text-secondary small" style="font-size: 10.5px;">Tanggal: {{ $metrics['tanggal_terkini'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 2: Rata-rata Pendapatan -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 mb-0 dashboard-card-hover bg-white" style="padding: 16px 20px; border-radius: 12px;">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-primary-subtle text-primary me-3">
                        <i class="bi bi-calculator fs-5"></i>
                    </div>
                    <div>
                        <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Rata-Rata</span>
                        <h4 class="fw-bold text-dark mb-0" style="font-size: 18px;">{{ $metrics['rata_rata'] }}</h4>
                        <span class="text-secondary small" style="font-size: 10.5px;">Berdasarkan {{ $metrics['total_data'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 3: Akurasi SVR-GWO -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 mb-0 dashboard-card-hover bg-white" style="padding: 16px 20px; border-radius: 12px;">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-warning-subtle text-warning me-3">
                        <i class="bi bi-cpu fs-5"></i>
                    </div>
                    <div>
                        <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Akurasi SVR-GWO</span>
                        <h4 class="fw-bold text-dark mb-0" style="font-size: 18px;">{{ $metrics['mape_gwo'] }}</h4>
                        <span class="text-secondary small" style="font-size: 10.5px;">MAPE Grey Wolf Optimizer</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 4: Akurasi SVR-Grid Search -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 mb-0 dashboard-card-hover bg-white" style="padding: 16px 20px; border-radius: 12px;">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-info-subtle text-info me-3">
                        <i class="bi bi-grid-3x3 me-3"></i>
                    </div>
                    <div>
                        <span class="text-uppercase text-secondary fw-semibold d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Akurasi SVR-Grid Search</span>
                        <h4 class="fw-bold text-dark mb-0" style="font-size: 18px;">{{ $metrics['mape_gs'] }}</h4>
                        <span class="text-secondary small" style="font-size: 10.5px;">MAPE Grid Search tuning</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Section: 2 Columns -->
    <div class="row g-4">
        <!-- Left Column: Chart & Table (8/12) -->
        <div class="col-12 col-lg-8">
            <!-- Dynamic Chart Card -->
            <div class="card mb-4 bg-white" style="border-radius: 12px; padding: 20px 24px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark" style="font-size: 14px;">Komparasi Prediksi SVR (GWO vs Grid Search)</h5>
                        <span class="text-secondary small d-block" style="font-size: 11px;">Menampilkan tren fluktuasi realisasi pendapatan dibandingkan dengan hasil prediksi optimasi GWO dan Grid Search</span>
                    </div>
                    <a href="{{ route('operator.prediksi.index') }}" class="btn btn-outline-primary btn-sm rounded-2" style="font-size: 11.5px; padding: 4px 10px;">
                        <i class="bi bi-cpu me-1"></i> Latih SVR
                    </a>
                </div>
                <div style="height: 260px; position: relative;">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>

            <!-- Recent Incomes Card -->
            <div class="card bg-white mb-0" style="border-radius: 12px; padding: 20px 24px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark" style="font-size: 14px;">Data Pendapatan Terkini</h5>
                        <span class="text-secondary small d-block" style="font-size: 11px;">Realisasi pendapatan harian yang terakhir kali terinput ke sistem</span>
                    </div>
                    <a href="{{ route('operator.pendapatan.index') }}" class="btn btn-primary btn-sm rounded-2" style="font-size: 11.5px; padding: 4px 10px;">
                        <i class="bi bi-pencil-square me-1"></i> Kelola Data
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
                        <thead>
                            <tr class="table-light">
                                <th style="width: 50px; padding: 8px 12px;">No</th>
                                <th style="padding: 8px 12px;">Tanggal</th>
                                <th style="padding: 8px 12px;">Rayon</th>
                                <th style="padding: 8px 12px;">Petugas / Jukir</th>
                                <th style="text-align: right; padding: 8px 12px;">Jumlah Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomes as $index => $income)
                                <tr>
                                    <td style="padding: 10px 12px;">{{ $index + 1 }}</td>
                                    <td style="padding: 10px 12px;">{{ date('d-m-Y', strtotime($income->tanggal)) }}</td>
                                    <td style="padding: 10px 12px;">
                                        <span class="badge bg-primary-subtle text-primary px-2 py-1" style="font-size: 10px;">
                                            {{ $income->rayon->nama_rayon ?? 'Tidak Diketahui' }}
                                        </span>
                                    </td>
                                    <td style="padding: 10px 12px;">
                                        {{ $income->juruParkir->jumlah_juru_parkir ?? ($income->rayon->jumlah_juru_parkir ?? 0) }} Jukir
                                    </td>
                                    <td style="text-align: right; font-weight: 600; color: var(--primary-blue); padding: 10px 12px;">
                                        Rp {{ number_format($income->jumlah, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-4" style="padding: 10px 12px;">Belum ada data pendapatan terinput.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Sidebar / Quick Actions & Rayon Stats (4/12) -->
        <div class="col-12 col-lg-4">
            <!-- Quick Actions Panel -->
            <div class="card mb-4 bg-white" style="border-radius: 12px; padding: 20px 24px;">
                <h5 class="fw-bold mb-3 text-dark pb-2 border-bottom" style="font-size: 14px;">Aksi Cepat</h5>
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('operator.pendapatan.index') }}" class="quick-action-btn">
                        <i class="bi bi-file-earmark-plus me-3 text-primary fs-6"></i>
                        <div>
                            <strong class="d-block small text-dark" style="font-size: 12.5px;">Input Pendapatan</strong>
                            <span class="text-secondary d-block" style="font-size: 10px; line-height: 1.2;">Catat pendapatan harian rayon</span>
                        </div>
                    </a>
                    <a href="{{ route('operator.prediksi.index') }}" class="quick-action-btn">
                        <i class="bi bi-cpu me-3 text-warning fs-6"></i>
                        <div>
                            <strong class="d-block small text-dark" style="font-size: 12.5px;">Latih Model SVR</strong>
                            <span class="text-secondary d-block" style="font-size: 10px; line-height: 1.2;">Perbarui kalkulasi prediksi</span>
                        </div>
                    </a>
                    <a href="{{ route('operator.hari-libur.index') }}" class="quick-action-btn">
                        <i class="bi bi-calendar-event me-3 text-danger fs-6"></i>
                        <div>
                            <strong class="d-block small text-dark" style="font-size: 12.5px;">Kelola Hari Libur</strong>
                            <span class="text-secondary d-block" style="font-size: 10px; line-height: 1.2;">Atur kalender libur daerah</span>
                        </div>
                    </a>
                    <a href="{{ route('operator.laporan.index') }}" class="quick-action-btn">
                        <i class="bi bi-file-earmark-pdf me-3 text-success fs-6"></i>
                        <div>
                            <strong class="d-block small text-dark" style="font-size: 12.5px;">Laporan Pendapatan</strong>
                            <span class="text-secondary d-block" style="font-size: 10px; line-height: 1.2;">Cetak atau ekspor file laporan</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Rayons Stats Card -->
            <div class="card bg-white" style="border-radius: 12px; padding: 20px 24px;">
                <h5 class="fw-bold mb-3 text-dark pb-2 border-bottom" style="font-size: 14px;">Daftar Rayon Aktif</h5>
                <div class="d-flex flex-column gap-3">
                    @forelse($rayons as $rayon)
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 28px; height: 28px; font-size: 11px; font-weight: 600;">
                                    {{ substr($rayon->nama_rayon, -1) }}
                                </div>
                                <div>
                                    <strong class="d-block text-dark small" style="font-size: 12.5px;">{{ $rayon->nama_rayon }}</strong>
                                    <span class="text-secondary d-block" style="font-size: 10.5px; line-height: 1.2;">{{ $rayon->kecamatan }}</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">{{ $rayon->jumlah_juru_parkir }} Jukir</span>
                                <small class="d-block text-muted mt-1" style="font-size: 9.5px;">{{ $rayon->pendapatans_count }} entri</small>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-3 text-secondary small">Belum ada data rayon terdaftar.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('revenueTrendChart').getContext('2d');
        
        // Gradient fill for area chart
        const gradientActual = ctx.createLinearGradient(0, 0, 0, 260);
        gradientActual.addColorStop(0, 'rgba(0, 91, 170, 0.12)');
        gradientActual.addColorStop(1, 'rgba(0, 91, 170, 0.0)');

        const gradientPredictGwo = ctx.createLinearGradient(0, 0, 0, 260);
        gradientPredictGwo.addColorStop(0, 'rgba(244, 197, 66, 0.08)');
        gradientPredictGwo.addColorStop(1, 'rgba(244, 197, 66, 0.0)');

        const gradientPredictGs = ctx.createLinearGradient(0, 0, 0, 260);
        gradientPredictGs.addColorStop(0, 'rgba(197, 90, 17, 0.08)');
        gradientPredictGs.addColorStop(1, 'rgba(197, 90, 17, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Realisasi Pendapatan',
                        data: @json($chartActualValues),
                        borderColor: '#005BAA',
                        borderWidth: 2.5,
                        backgroundColor: gradientActual,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#005BAA',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 3.5,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'Prediksi SVR-GWO',
                        data: @json($chartPredictGwoValues),
                        borderColor: '#F4C542',
                        borderWidth: 2.5,
                        backgroundColor: gradientPredictGwo,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#F4C542',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 3.5,
                        pointHoverRadius: 5,
                        borderDash: [4, 4]
                    },
                    {
                        label: 'Prediksi SVR-Grid Search',
                        data: @json($chartPredictGsValues),
                        borderColor: '#C55A11',
                        borderWidth: 2.5,
                        backgroundColor: gradientPredictGs,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#C55A11',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 3.5,
                        pointHoverRadius: 5,
                        borderDash: [4, 4]
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
                            boxWidth: 8,
                            padding: 15,
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
                        padding: 8,
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
                                size: 10.5
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
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
