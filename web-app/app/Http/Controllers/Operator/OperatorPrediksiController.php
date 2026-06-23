<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Pendapatan;
use App\Models\Rayon;
use App\Models\JuruParkir;
use App\Models\HariLibur;
use App\Models\ModelRun;
use App\Models\ModelParameter;
use App\Models\ModelMetric;
use App\Models\PredictionResult;
use Illuminate\Http\Request;
use App\Services\FastApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OperatorPrediksiController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ringkasan Dataset dari database
        $totalPendapatan = Pendapatan::count();
        $periodeAwal = Pendapatan::min('tanggal');
        $periodeAkhir = Pendapatan::max('tanggal');
        
        $periodeAwalFormatted = $periodeAwal ? Carbon::parse($periodeAwal)->translatedFormat('d F Y') : '-';
        $periodeAkhirFormatted = $periodeAkhir ? Carbon::parse($periodeAkhir)->translatedFormat('d F Y') : '-';
        
        $jumlahRayon = Rayon::count();
        
        // Count weekend and holidays in dataset years only
        $minYear = $periodeAwal ? Carbon::parse($periodeAwal)->year : 2023;
        $maxYear = $periodeAkhir ? Carbon::parse($periodeAkhir)->year : 2025;
        $startDate = Carbon::create($minYear, 1, 1)->format('Y-m-d');
        $endDate = Carbon::create($maxYear, 12, 31)->format('Y-m-d');

        $jumlahHariLibur = HariLibur::where('tipe', 'Libur Nasional')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->count();
        $jumlahWeekend = HariLibur::where('tipe', 'Weekend')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->count();
        
        // 2. Validasi Kelengkapan Dataset
        $hasPendapatan = $totalPendapatan > 0;
        $hasRayon = $jumlahRayon > 0;
        $hasJuruParkir = JuruParkir::count() > 0;
        $hasHariLibur = HariLibur::count() > 0;
        
        // Kolom validation (statis sukses karena struktur tabel dijamin migrasi)
        $colTanggal = true;
        $colRayon = true;
        $colJukir = true;
        $colTotalPendapatan = true;
        
        $datasetReady = $hasPendapatan && $hasRayon && $hasJuruParkir && $hasHariLibur;
        
        // 3. Ambil data run SVR default terakhir jika ada
        $lastRun = ModelRun::where('model_type', 'svr_default')
            ->where('status', 'success')
            ->orderBy('id', 'desc')
            ->first();
            
        $params = null;
        $metrics = null;
        $predictions = collect([]);
        $chartData = collect([]);
        
        if ($lastRun) {
            $params = $lastRun->modelParameter;
            $metrics = $lastRun->modelMetrics()->where('dataset_type', 'test')->first();
            
            // Query for graph (all test predictions sorted chronologically)
            if ($request->filled('rayon_id') && $request->rayon_id > 0) {
                $chartData = $lastRun->predictionResults()
                    ->where('rayon_id', $request->rayon_id)
                    ->orderBy('tanggal', 'asc')
                    ->get();
            } else {
                $chartData = $lastRun->predictionResults()
                    ->select('tanggal', DB::raw('SUM(actual_value) as actual_value'), DB::raw('SUM(predicted_value) as predicted_value'))
                    ->groupBy('tanggal')
                    ->orderBy('tanggal', 'asc')
                    ->get();
            }
                
            // Query for table (with pagination and optional filters)
            $predictionsQuery = $lastRun->predictionResults()->orderBy('tanggal', 'desc');
            
            if ($request->filled('rayon_id') && $request->rayon_id > 0) {
                $predictionsQuery->where('rayon_id', $request->rayon_id);
            }
            
            $predictions = $predictionsQuery->paginate(10)->withQueryString();
        }
        
        $rayons = Rayon::all();

        // 4. Fetch raw dataset snapshot for preview
        $rawSnapshotQuery = Pendapatan::with(['rayon', 'juruParkir'])->orderBy('tanggal', 'asc')->limit(5)->get();
        $rawSnapshot = [];
        foreach ($rawSnapshotQuery as $p) {
            $tgl = Carbon::parse($p->tanggal)->format('Y-m-d');
            $isLibur = HariLibur::where('tipe', 'Libur Nasional')->where('tanggal', $tgl)->exists() ? 1 : 0;
            
            $dayOfWeek = (int) date('N', strtotime($tgl));
            $isWeekend = (HariLibur::where('tipe', 'Weekend')->where('tanggal', $tgl)->exists() || $dayOfWeek >= 6) ? 1 : 0;
            
            $jukirCount = $p->juruParkir->jumlah_juru_parkir ?? ($p->rayon->jumlah_juru_parkir ?? 80);
            
            $rawSnapshot[] = [
                'tanggal' => $tgl,
                'rayon_id' => $p->rayon_id,
                'rayon_name' => $p->rayon->nama_rayon ?? '-',
                'weekend' => $isWeekend,
                'jumlah_jukir' => $jukirCount,
                'total_pendapatan' => $p->jumlah,
                'libur_nasional' => $isLibur
            ];
        }
        
        return view('operator.prediksi.index', compact(
            'totalPendapatan',
            'periodeAwalFormatted',
            'periodeAkhirFormatted',
            'jumlahRayon',
            'jumlahHariLibur',
            'jumlahWeekend',
            'hasPendapatan',
            'hasRayon',
            'hasJuruParkir',
            'hasHariLibur',
            'colTanggal',
            'colRayon',
            'colJukir',
            'colTotalPendapatan',
            'datasetReady',
            'lastRun',
            'params',
            'metrics',
            'predictions',
            'chartData',
            'rayons',
            'rawSnapshot'
        ));
    }

    public function runSvr(Request $request)
    {
        // 1. Validasi dataset
        $totalPendapatan = Pendapatan::count();
        $jumlahRayon = Rayon::count();
        $jumlahJuruParkir = JuruParkir::count();
        $jumlahHariLibur = HariLibur::count();
        
        if ($totalPendapatan == 0 || $jumlahRayon == 0 || $jumlahJuruParkir == 0 || $jumlahHariLibur == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Lengkapi dataset terlebih dahulu sebelum menjalankan prediksi.'
            ], 422);
        }
        
        // 2. Ambil data transaksi pendapatan dan relasi
        $pendapatanData = Pendapatan::with(['rayon', 'juruParkir'])->orderBy('tanggal', 'asc')->get();
        
        $periodeAwal = Pendapatan::min('tanggal');
        $periodeAkhir = Pendapatan::max('tanggal');
        $minYear = $periodeAwal ? Carbon::parse($periodeAwal)->year : 2023;
        $maxYear = $periodeAkhir ? Carbon::parse($periodeAkhir)->year : 2025;
        $startDate = Carbon::create($minYear, 1, 1)->format('Y-m-d');
        $endDate = Carbon::create($maxYear, 12, 31)->format('Y-m-d');

        $liburNasionalDates = HariLibur::where('tipe', 'Libur Nasional')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->pluck('tanggal')
            ->map(fn($t) => Carbon::parse($t)->format('Y-m-d'))
            ->toArray();
            
        $weekendDates = HariLibur::where('tipe', 'Weekend')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->pluck('tanggal')
            ->map(fn($t) => Carbon::parse($t)->format('Y-m-d'))
            ->toArray();
        
        $dataset = [];
        foreach ($pendapatanData as $p) {
            $tgl = Carbon::parse($p->tanggal)->format('Y-m-d');
            $isLibur = in_array($tgl, $liburNasionalDates) ? 1 : 0;
            
            $dayOfWeek = (int) date('N', strtotime($tgl));
            $isWeekend = (in_array($tgl, $weekendDates) || $dayOfWeek >= 6) ? 1 : 0;
            
            $jukirCount = $p->juruParkir->jumlah_juru_parkir ?? ($p->rayon->jumlah_juru_parkir ?? 80);
            
            $dataset[] = [
                'Tanggal' => $tgl,
                'Rayon' => (int) $p->rayon_id,
                'Weekend' => $isWeekend,
                'Jumlah Jukir' => (int) $jukirCount,
                'Total_Pendapatan' => (double) $p->jumlah,
                'Libur_Nasional' => $isLibur
            ];
        }
        
        // 3. Catat running model_run
        $modelRun = ModelRun::create([
            'model_name' => 'SVR Standar',
            'model_type' => 'svr_default',
            'status' => 'running',
            'started_at' => now(),
            'created_by' => auth()->user()->username ?? 'operator',
        ]);
        
        // 4. Kirim request ke FastAPI
        try {
            $fastApiService = app(FastApiService::class);
            $response = $fastApiService->post('train/svr-default', [
                'mode' => 'svr_default',
                'requested_by' => auth()->user()->username ?? 'operator',
                'timestamp' => time(),
                'dataset' => $dataset
            ]);
            
            if ($response === null) {
                $modelRun->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'error_message' => 'Service Python belum aktif. Jalankan Python API terlebih dahulu untuk memproses model SVR.'
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Service Python belum aktif. Jalankan Python API terlebih dahulu untuk memproses model SVR.'
                ], 503);
            }
            
            if (isset($response['status']) && $response['status'] === 'success') {
                DB::beginTransaction();
                try {
                    // Update model_run status
                    $modelRun->update([
                        'status' => 'success',
                        'finished_at' => now(),
                        'total_rows' => $response['dataset']['total_rows'],
                        'train_rows' => $response['dataset']['train_rows'],
                        'test_rows' => $response['dataset']['test_rows'],
                        'train_period' => $response['dataset']['train_period'],
                        'test_period' => $response['dataset']['test_period'],
                    ]);
                    
                    // Simpan Parameter
                    ModelParameter::create([
                        'model_run_id' => $modelRun->id,
                        'kernel' => $response['parameters']['kernel'],
                        'c_value' => $response['parameters']['C'],
                        'epsilon_value' => $response['parameters']['epsilon'],
                        'gamma_value' => (string) $response['parameters']['gamma'],
                    ]);
                    
                    // Simpan Metrik
                    ModelMetric::create([
                        'model_run_id' => $modelRun->id,
                        'mae' => $response['metrics']['mae'],
                        'rmse' => $response['metrics']['rmse'],
                        'mape' => $response['metrics']['mape'],
                        'r2_score' => $response['metrics']['r2'],
                        'accuracy' => $response['metrics']['accuracy'],
                        'dataset_type' => 'test'
                    ]);
                    
                    // Simpan Hasil Prediksi
                    $predictionsData = [];
                    foreach ($response['predictions'] as $pred) {
                        $predictionsData[] = [
                            'model_run_id' => $modelRun->id,
                            'tanggal' => $pred['tanggal'],
                            'rayon_id' => $pred['rayon_id'],
                            'rayon_name' => $pred['rayon'],
                            'actual_value' => $pred['actual'],
                            'predicted_value' => $pred['predicted'],
                            'error_value' => $pred['error'],
                            'percentage_error' => $pred['percentage_error'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                    
                    foreach (array_chunk($predictionsData, 500) as $chunk) {
                        PredictionResult::insert($chunk);
                    }
                    
                    DB::commit();
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Proses generate prediksi SVR standar berhasil diselesaikan.'
                    ]);
                    
                } catch (\Exception $dbEx) {
                    DB::rollBack();
                    $modelRun->update([
                        'status' => 'failed',
                        'finished_at' => now(),
                        'error_message' => 'Gagal menyimpan hasil prediksi ke database: ' . $dbEx->getMessage()
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal menyimpan hasil prediksi ke database: ' . $dbEx->getMessage()
                    ], 500);
                }
            } else {
                $errMsg = $response['detail'] ?? 'Kesalahan tidak diketahui pada service Python.';
                $modelRun->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'error_message' => $errMsg
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Proses generate prediksi SVR standar gagal: ' . $errMsg
                ], 500);
            }
            
        } catch (\Exception $ex) {
            $modelRun->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $ex->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi pengecualian saat eksekusi: ' . $ex->getMessage()
            ], 500);
        }
    }

    public function resetSvr()
    {
        DB::beginTransaction();
        try {
            // Hapus semua model run dengan tipe svr_default (cascade delete akan menghapus parameter, metrik, dan hasil prediksi)
            ModelRun::where('model_type', 'svr_default')->delete();
            
            DB::commit();
            
            return redirect()->route('operator.prediksi.index')->with('success', 'Model SVR Standar berhasil di-reset. Semua riwayat training dan prediksi telah dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('operator.prediksi.index')->with('error', 'Gagal melakukan reset model: ' . $e->getMessage());
        }
    }
}
