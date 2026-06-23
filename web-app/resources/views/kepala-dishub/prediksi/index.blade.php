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

    <!-- Card Analisis & Rekomendasi Model -->
    <div class="card mb-4 bg-white shadow-sm border border-light">
        <div class="card-body">
            <h5 class="card-title text-dark mb-3"><i class="bi bi-chat-left-text-fill me-2 text-primary"></i>Analisis Kinerja & Rekomendasi Model</h5>
            
            @php
                // Parse values from mock array
                preg_match('/([0-9.]+)/', str_replace('.', '', $metrics['mae']), $maeMatches);
                $mae = isset($maeMatches[1]) ? (double) $maeMatches[1] : 72400;

                preg_match('/([0-9.]+)/', str_replace('.', '', $metrics['rmse']), $rmseMatches);
                $rmse = isset($rmseMatches[1]) ? (double) $rmseMatches[1] : 102500;

                preg_match('/([0-9,]+)/', $metrics['mape'], $mapeMatches);
                $mape = isset($mapeMatches[1]) ? (double) str_replace(',', '.', $mapeMatches[1]) : 4.82;

                preg_match('/([0-9,.-]+)/', $metrics['r2'], $r2Matches);
                $r2 = isset($r2Matches[1]) ? (double) str_replace(',', '.', $r2Matches[1]) : 0.93;
                
                // Rata-rata pendapatan aktual mock untuk kebutuhan presentasi / data simulasi
                $meanActual = 1500000;
                $rmsePercentage = ($rmse / $meanActual) * 100;
                
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

    <!-- Grafik & Hasil Table Section -->
    <div class="row g-4">
        <!-- Chart placeholder -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="card-title">Grafik Aktual vs Prediksi</h5>
                        <div class="chart-placeholder" style="height: 320px;">
                            <span class="fs-5 fw-medium"><i class="bi bi-graph-up-arrow fs-3 d-block text-center mb-2"></i>[ Visualisasi Tren Prediksi ]</span>
                            <span class="text-secondary small">Menampilkan perbandingan tren pendapatan realisasi vs peramalan SVR</span>
                        </div>
                    </div>
                    <!-- Detailed Graph Analysis (Mock) -->
                    <div class="mt-3 p-3 bg-light rounded-3 border-start border-4 border-primary">
                        <h6 class="fw-bold text-dark mb-1 text-sm"><i class="bi bi-info-circle-fill text-primary me-1"></i>Analisis Pola & Kesesuaian Tren Grafik</h6>
                        <p class="text-secondary small mb-0" style="font-size: 12px; line-height: 1.5;">
                            Secara umum, grafik menunjukkan tren pergerakan hasil prediksi SVR + GWO sangat selaras dengan realisasi aktual bulanan. Deviasi akumulasi kumulatif bulanan tercatat sangat kecil (hanya <strong>0,28%</strong>). Puncak pendapatan aktual dan prediksi terjadi bersamaan pada bulan **Mei 2026** (Aktual: Rp 192,8 Juta, Prediksi: Rp 190,5 Juta).
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table list (5 Months) -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
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
                    <!-- Detailed Table Analysis (Mock) -->
                    <div class="mt-3 p-3 bg-light rounded-3 border-start border-4 border-success">
                        <h6 class="fw-bold text-dark mb-1 text-sm"><i class="bi bi-grid-3x3-gap-fill text-success me-1"></i>Analisis Akurasi Hasil Prediksi</h6>
                        <p class="text-secondary small mb-0" style="font-size: 12px; line-height: 1.5;">
                            Berdasarkan data 5 bulan terakhir, tingkat akurasi rata-rata (MAPE) mencapai **4,82%** (kategori **Sangat Akurat**). Selisih deviasi nominal terkecil berada di bulan **Februari 2026** (selisih Rp 1.310.000), sedangkan selisih terbesar berada di bulan **Maret 2026** (selisih Rp 2.310.000). Secara spasial, seluruh rayon menunjukkan tingkat kepatuhan setoran yang tinggi dengan simpangan yang minimal.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
