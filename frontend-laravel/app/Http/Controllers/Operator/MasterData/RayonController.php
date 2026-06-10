<?php

namespace App\Http\Controllers\Operator\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Rayon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RayonController extends Controller
{
    public function index(Request $request)
    {
        $rayons = Rayon::when($request->filled('search'), function($query) use ($request) {
            $search = $request->search;
            $query->where('nama_rayon', 'like', "%{$search}%")
                  ->orWhere('kecamatan', 'like', "%{$search}%")
                  ->orWhere('lokasi', 'like', "%{$search}%")
                  ->orWhere('karakteristik_area', 'like', "%{$search}%");
        })->paginate(10)->onEachSide(1)->withQueryString();

        return view('operator.master-data.rayon.index', compact('rayons'));
    }

    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
    {
        $rules = [
            'nama_rayon' => 'required|string|max:255|unique:rayons,nama_rayon',
            'kecamatan' => 'required|string|max:255',
            'lokasi' => 'required|string|max:255',
            'karakteristik_area' => 'required|string|max:255',
            'jumlah_juru_parkir' => 'required|integer|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        Rayon::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data Rayon berhasil ditambahkan.'
        ]);
    }

    public function show($id)
    {
        $rayon = Rayon::findOrFail($id);
        return response()->json($rayon);
    }

    public function edit($id)
    {
        $rayon = Rayon::findOrFail($id);
        return response()->json($rayon);
    }

    public function update(Request $request, $id)
    {
        $rayon = Rayon::findOrFail($id);

        $rules = [
            'nama_rayon' => 'required|string|max:255|unique:rayons,nama_rayon,' . $id,
            'kecamatan' => 'required|string|max:255',
            'lokasi' => 'required|string|max:255',
            'karakteristik_area' => 'required|string|max:255',
            'jumlah_juru_parkir' => 'required|integer|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $rayon->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data Rayon berhasil diperbarui.'
        ]);
    }

    public function destroy($id)
    {
        $rayon = Rayon::findOrFail($id);

        if ($rayon->pendapatans()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Rayon tidak dapat dihapus karena memiliki data riwayat pendapatan. Hapus data pendapatan terlebih dahulu.'
            ], 400);
        }

        $rayon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data Rayon berhasil dihapus.'
        ]);
    }
}
