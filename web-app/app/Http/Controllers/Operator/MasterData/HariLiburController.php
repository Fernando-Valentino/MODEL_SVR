<?php

namespace App\Http\Controllers\Operator\MasterData;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class HariLiburController extends Controller
{
    public function index(Request $request)
    {
        $query = HariLibur::orderBy('tanggal', 'desc');

        if ($request->filled('search')) {
            $query->where('keterangan', 'like', "%{$request->search}%");
        }

        $year = $request->get('year');
        if ($year === null) {
            $year = date('Y');
        }

        if ($year !== 'all' && $year !== '') {
            $query->whereYear('tanggal', $year);
        }

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        $hariLiburs = $query->paginate(10)->onEachSide(1)->withQueryString();
        
        // Map day names to Indonesian
        $dayMapping = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];

        foreach ($hariLiburs as $libur) {
            $englishDay = Carbon::parse($libur->tanggal)->format('l');
            $libur->hari = $dayMapping[$englishDay] ?? $englishDay;
        }

        // Fetch distinct years dynamically in a DB-agnostic way, always including current year
        $availableYears = HariLibur::orderBy('tanggal', 'desc')->pluck('tanggal')
            ->map(fn($date) => Carbon::parse($date)->format('Y'))
            ->push(date('Y'))
            ->unique()
            ->values()
            ->sortDesc();

        return view('operator.master-data.hari-libur.index', compact('hariLiburs', 'availableYears'));
    }

    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
    {
        $rules = [
            'tanggal' => 'required|date|unique:hari_liburs,tanggal',
            'keterangan' => 'required|string|max:255',
            'tipe' => 'required|in:Libur Nasional,Weekend',
        ];

        $messages = [
            'tanggal.unique' => 'Tanggal ini sudah terdaftar sebagai hari libur.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        HariLibur::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Hari Libur berhasil ditambahkan.'
        ]);
    }

    public function show($id)
    {
        $hariLibur = HariLibur::findOrFail($id);
        return response()->json($hariLibur);
    }

    public function edit($id)
    {
        $hariLibur = HariLibur::findOrFail($id);
        return response()->json($hariLibur);
    }

    public function update(Request $request, $id)
    {
        $hariLibur = HariLibur::findOrFail($id);

        $rules = [
            'tanggal' => 'required|date|unique:hari_liburs,tanggal,' . $id,
            'keterangan' => 'required|string|max:255',
            'tipe' => 'required|in:Libur Nasional,Weekend',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $hariLibur->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Hari Libur berhasil diperbarui.'
        ]);
    }

    public function destroy($id)
    {
        $hariLibur = HariLibur::findOrFail($id);
        $hariLibur->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hari Libur berhasil dihapus.'
        ]);
    }

    public function generate(Request $request)
    {
        $rules = [
            'year' => 'required|integer|min:2020|max:2030',
            'import_api' => 'nullable|boolean',
            'generate_weekend' => 'nullable|boolean',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $year = $request->year;
        
        // Prevent generation if data for this year already exists
        $hasExistingData = HariLibur::whereYear('tanggal', $year)->exists();
        if ($hasExistingData) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'year' => ["Data Hari Libur / Weekend untuk tahun {$year} sudah ada di database."]
                ]
            ], 422);
        }

        $importApi = $request->boolean('import_api');
        $generateWeekend = $request->boolean('generate_weekend');

        $insertedLibur = 0;
        $insertedWeekend = 0;

        // 1. Fetch from API
        if ($importApi) {
            try {
                $response = Http::timeout(10)->get("https://api-hari-libur.vercel.app/api?year={$year}");
                if ($response->successful()) {
                    $resData = $response->json();
                    $holidays = $resData['data'] ?? [];

                    foreach ($holidays as $item) {
                        $dateStr = $item['date'] ?? null;
                        $desc = $item['description'] ?? 'Hari Libur';

                        if ($dateStr) {
                            $exists = HariLibur::where('tanggal', $dateStr)->exists();
                            if (!$exists) {
                                HariLibur::create([
                                    'tanggal' => $dateStr,
                                    'keterangan' => $desc,
                                    'tipe' => 'Libur Nasional',
                                ]);
                                $insertedLibur++;
                            }
                        }
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengambil data dari API Kalender. Silakan coba lagi.'
                    ], 400);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menghubungi API Kalender: ' . $e->getMessage()
                ], 500);
            }
        }

        // 2. Generate Saturdays & Sundays (Weekend)
        if ($generateWeekend) {
            $startDate = Carbon::createFromDate($year, 1, 1);
            $endDate = Carbon::createFromDate($year, 12, 31);

            $dayMapping = [
                'Sunday' => 'Minggu',
                'Monday' => 'Senin',
                'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis',
                'Friday' => 'Jumat',
                'Saturday' => 'Sabtu'
            ];

            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                if ($date->isWeekend()) {
                    $dateString = $date->toDateString();
                    $exists = HariLibur::where('tanggal', $dateString)->exists();

                    if (!$exists) {
                        $englishDay = $date->format('l');
                        $indonesianDay = $dayMapping[$englishDay] ?? $englishDay;

                        HariLibur::create([
                            'tanggal' => $dateString,
                            'keterangan' => "Weekend ({$indonesianDay})",
                            'tipe' => 'Weekend',
                        ]);
                        $insertedWeekend++;
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Proses berhasil. Ditambahkan {$insertedLibur} Hari Libur Nasional dan {$insertedWeekend} Hari Weekend untuk tahun {$year}."
        ]);
    }
}
