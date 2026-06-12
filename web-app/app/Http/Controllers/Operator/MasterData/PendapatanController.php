<?php

namespace App\Http\Controllers\Operator\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Pendapatan;
use App\Models\Rayon;
use App\Models\JuruParkir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PendapatanController extends Controller
{
    public function index(Request $request)
    {
        $query = Pendapatan::with(['rayon', 'juruParkir'])->orderBy('tanggal', 'desc');

        // Filter by Date Range
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_selesai);
        }

        // Filter by Rayon
        if ($request->filled('rayon_id')) {
            $query->where('rayon_id', $request->rayon_id);
        }

        // Search by Rayon Name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('rayon', function($q) use ($search) {
                $q->where('nama_rayon', 'like', "%{$search}%");
            });
        }

        $pendapatans = $query->paginate(10)->onEachSide(1)->withQueryString();
        $rayons = Rayon::all();

        return view('operator.master-data.pendapatan.index', compact('pendapatans', 'rayons'));
    }

    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
    {
        $rules = [
            'tanggal' => 'required|date',
            'rayon_id' => 'required|exists:rayons,id',
            'jumlah' => 'required|numeric|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if there is already an entry for this Rayon and Date
        $existing = Pendapatan::where('tanggal', $request->tanggal)
            ->where('rayon_id', $request->rayon_id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'tanggal' => ['Sudah ada data pendapatan untuk Rayon ini pada tanggal yang dipilih.']
                ]
            ], 422);
        }

        // Find or create matching JuruParkir record
        $juruParkir = JuruParkir::where('rayon_id', $request->rayon_id)->first();
        if (!$juruParkir) {
            $rayon = Rayon::find($request->rayon_id);
            $juruParkir = JuruParkir::create([
                'rayon_id' => $request->rayon_id,
                'jumlah_juru_parkir' => $rayon ? $rayon->jumlah_juru_parkir : 80
            ]);
        }

        Pendapatan::create([
            'tanggal' => $request->tanggal,
            'rayon_id' => $request->rayon_id,
            'juru_parkir_id' => $juruParkir->id,
            'jumlah' => $request->jumlah
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Pendapatan berhasil ditambahkan.'
        ]);
    }

    public function show($id)
    {
        $pendapatan = Pendapatan::with('rayon')->findOrFail($id);
        return response()->json($pendapatan);
    }

    public function edit($id)
    {
        $pendapatan = Pendapatan::findOrFail($id);
        return response()->json($pendapatan);
    }

    public function update(Request $request, $id)
    {
        $pendapatan = Pendapatan::findOrFail($id);

        $rules = [
            'tanggal' => 'required|date',
            'rayon_id' => 'required|exists:rayons,id',
            'jumlah' => 'required|numeric|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check uniqueness excluding current record
        $existing = Pendapatan::where('tanggal', $request->tanggal)
            ->where('rayon_id', $request->rayon_id)
            ->where('id', '!=', $id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'tanggal' => ['Sudah ada data pendapatan untuk Rayon ini pada tanggal yang dipilih.']
                ]
            ], 422);
        }

        // Find or create matching JuruParkir record
        $juruParkir = JuruParkir::where('rayon_id', $request->rayon_id)->first();
        if (!$juruParkir) {
            $rayon = Rayon::find($request->rayon_id);
            $juruParkir = JuruParkir::create([
                'rayon_id' => $request->rayon_id,
                'jumlah_juru_parkir' => $rayon ? $rayon->jumlah_juru_parkir : 80
            ]);
        }

        $pendapatan->update([
            'tanggal' => $request->tanggal,
            'rayon_id' => $request->rayon_id,
            'juru_parkir_id' => $juruParkir->id,
            'jumlah' => $request->jumlah
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Pendapatan berhasil diperbarui.'
        ]);
    }

    public function destroy($id)
    {
        $pendapatan = Pendapatan::findOrFail($id);
        $pendapatan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data Pendapatan berhasil dihapus.'
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuka file.'
            ], 400);
        }

        // Read header row
        $header = fgetcsv($handle, 1000, ',');
        
        if (!$header) {
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'File CSV kosong atau tidak valid.'
            ], 422);
        }

        // Normalize headers
        $header = array_map(function($h) {
            return strtolower(trim(str_replace('"', '', $h)));
        }, $header);

        // Find indices
        $idxTanggal = array_search('tanggal', $header);
        $idxRayon = array_search('rayon', $header);
        
        $idxJukir = false;
        foreach (['jumlah jukir', 'jumlah_jukir', 'jumlah juru parkir', 'jukir', 'jumlah_juru_parkir'] as $variant) {
            if (($idx = array_search($variant, $header)) !== false) {
                $idxJukir = $idx;
                break;
            }
        }
        
        $idxPendapatan = false;
        foreach (['total pendapatan', 'total_pendapatan', 'jumlah', 'pendapatan', 'total'] as $variant) {
            if (($idx = array_search($variant, $header)) !== false) {
                $idxPendapatan = $idx;
                break;
            }
        }

        if ($idxTanggal === false || $idxRayon === false || $idxPendapatan === false) {
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'Format file tidak sesuai. Pastikan file CSV memiliki header Tanggal, Rayon, dan Total Pendapatan.'
            ], 422);
        }

        $importedCount = 0;
        $errors = [];
        $lineNum = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $lineNum++;
                
                // Skip empty or malformed rows
                if (empty($row) || count($row) < 3) continue;

                $tanggalStr = trim($row[$idxTanggal] ?? '');
                $rayonVal = trim($row[$idxRayon] ?? '');
                $jukirCount = $idxJukir !== false ? intval(trim($row[$idxJukir] ?? 0)) : null;
                $jumlahValStr = trim($row[$idxPendapatan] ?? 0);
                
                // Parse clean double value
                $jumlahVal = floatval(preg_replace('/[^\d.]/', '', str_replace(',', '.', $jumlahValStr)));

                if (empty($tanggalStr) || empty($rayonVal)) {
                    continue;
                }

                // Find Rayon
                $rayon = null;
                if (is_numeric($rayonVal)) {
                    $rayon = Rayon::find($rayonVal);
                } else {
                    $rayon = Rayon::where('nama_rayon', $rayonVal)
                        ->orWhere('nama_rayon', 'like', '%' . $rayonVal . '%')
                        ->first();
                }

                if (!$rayon) {
                    $romanMap = [1 => 'Rayon I', 2 => 'Rayon II', 3 => 'Rayon III', 4 => 'Rayon IV', 5 => 'Rayon V'];
                    if (isset($romanMap[intval($rayonVal)])) {
                        $rayon = Rayon::where('nama_rayon', $romanMap[intval($rayonVal)])->first();
                    }
                }

                if (!$rayon) {
                    $errors[] = "Baris {$lineNum}: Rayon '{$rayonVal}' tidak ditemukan di database.";
                    continue;
                }

                // Sync counts if provided
                if ($jukirCount !== null && $jukirCount > 0) {
                    $rayon->update(['jumlah_juru_parkir' => $jukirCount]);
                }

                // Find or create matching JuruParkir count record
                $juruParkir = JuruParkir::where('rayon_id', $rayon->id)->first();
                if (!$juruParkir) {
                    $juruParkir = JuruParkir::create([
                        'rayon_id' => $rayon->id,
                        'jumlah_juru_parkir' => $rayon->jumlah_juru_parkir ?? 80
                    ]);
                } else if ($jukirCount !== null && $jukirCount > 0) {
                    $juruParkir->update(['jumlah_juru_parkir' => $jukirCount]);
                }

                // Create or update Pendapatan
                Pendapatan::updateOrCreate(
                    [
                        'tanggal' => $tanggalStr,
                        'rayon_id' => $rayon->id,
                    ],
                    [
                        'juru_parkir_id' => $juruParkir->id,
                        'jumlah' => $jumlahVal
                    ]
                );

                $importedCount++;
            }
            
            if (count($errors) > 0 && $importedCount === 0) {
                DB::rollBack();
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengimpor data.',
                    'errors' => $errors
                ], 422);
            }

            DB::commit();
            fclose($handle);

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimpor {$importedCount} data pendapatan.",
                'warnings' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membaca file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_import_pendapatan.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Tanggal', 'Rayon', 'Jumlah Jukir', 'Total Pendapatan']);
            fputcsv($file, ['2026-06-01', 'Rayon I', '80', '1250000']);
            fputcsv($file, ['2026-06-01', 'Rayon II', '82', '1680000']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
