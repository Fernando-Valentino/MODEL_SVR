<?php

namespace App\Http\Controllers\Operator\MasterData;

use App\Http\Controllers\Controller;
use App\Models\JuruParkir;
use App\Models\Rayon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JuruParkirController extends Controller
{
    public function index(Request $request)
    {
        $query = JuruParkir::with('rayon');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('rayon', function($q) use ($search) {
                $q->where('nama_rayon', 'like', "%{$search}%")
                  ->orWhere('kecamatan', 'like', "%{$search}%");
            });
        }

        $juruParkirs = $query->paginate(10)->onEachSide(1)->withQueryString();
        
        // Find rayons that do not have a juru parkir record yet, for the Add Modal dropdown
        $assignedRayonIds = JuruParkir::pluck('rayon_id')->toArray();
        $availableRayons = Rayon::whereNotIn('id', $assignedRayonIds)->get();
        
        // Also fetch all rayons for the Edit dropdown/options
        $allRayons = Rayon::all();

        return view('operator.master-data.juru-parkir.index', compact('juruParkirs', 'availableRayons', 'allRayons'));
    }

    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
    {
        $rules = [
            'rayon_id' => 'required|exists:rayons,id|unique:juru_parkirs,rayon_id',
            'jumlah_juru_parkir' => 'required|integer|min:0',
        ];

        $messages = [
            'rayon_id.unique' => 'Rayon ini sudah memiliki data Juru Parkir. Silakan edit data yang ada.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $juruParkir = JuruParkir::create($request->all());

        // Sync Rayon's jumlah_juru_parkir column to maintain redundancy parity
        $rayon = Rayon::find($request->rayon_id);
        if ($rayon) {
            $rayon->update(['jumlah_juru_parkir' => $request->jumlah_juru_parkir]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data Juru Parkir berhasil ditambahkan.'
        ]);
    }

    public function show($id)
    {
        $juruParkir = JuruParkir::with('rayon')->findOrFail($id);
        return response()->json($juruParkir);
    }

    public function edit($id)
    {
        $juruParkir = JuruParkir::findOrFail($id);
        return response()->json($juruParkir);
    }

    public function update(Request $request, $id)
    {
        $juruParkir = JuruParkir::findOrFail($id);

        $rules = [
            'rayon_id' => 'required|exists:rayons,id|unique:juru_parkirs,rayon_id,' . $id,
            'jumlah_juru_parkir' => 'required|integer|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $juruParkir->update($request->all());

        // Sync Rayon's jumlah_juru_parkir column to maintain redundancy parity
        $rayon = Rayon::find($juruParkir->rayon_id);
        if ($rayon) {
            $rayon->update(['jumlah_juru_parkir' => $juruParkir->jumlah_juru_parkir]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data Juru Parkir berhasil diperbarui.'
        ]);
    }

    public function destroy($id)
    {
        $juruParkir = JuruParkir::findOrFail($id);

        // Prevent deletion if there are associated pendapatan records
        if ($juruParkir->pendapatans()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Data Juru Parkir ini tidak dapat dihapus karena memiliki riwayat pendapatan retribusi.'
            ], 400);
        }

        $juruParkir->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data Juru Parkir berhasil dihapus.'
        ]);
    }
}
