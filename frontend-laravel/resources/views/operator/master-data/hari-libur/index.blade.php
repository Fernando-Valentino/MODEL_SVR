@extends('layouts.app')

@section('title', 'Data Hari Libur & Weekend')
@section('subtitle', 'Halaman ini digunakan untuk mengelola data hari libur nasional dan akhir pekan sebagai faktor eksternal dalam prediksi pendapatan.')

@section('content')
<div class="container-fluid p-0">
    <!-- Toolbar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div class="d-flex gap-2 align-items-center">
            <div class="input-group" style="max-width: 240px;">
                <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                <input type="search" placeholder="Cari keterangan..." class="form-control" />
            </div>
            <select class="form-select" style="max-width: 140px;">
                <option value="2026">Tahun 2026</option>
                <option value="2025">Tahun 2025</option>
                <option value="2024">Tahun 2024</option>
                <option value="2023">Tahun 2023</option>
            </select>
            <select class="form-select" style="max-width: 160px;">
                <option value="">Semua Tipe</option>
                <option value="Libur Nasional">Libur Nasional</option>
                <option value="Weekend">Weekend</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark" onclick="alert('Fitur Tambah Data Hari Libur akan diimplementasikan pada tahap berikutnya.')">
                <i class="bi bi-plus-lg me-1"></i> Tambah Data
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Daftar Data Hari Libur & Weekend</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 65px;">No</th>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Keterangan</th>
                            <th style="width: 180px;">Tipe</th>
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hariLiburs as $libur)
                            <tr>
                                <td>{{ $libur['no'] }}</td>
                                <td style="font-weight: 600;">{{ $libur['tanggal'] }}</td>
                                <td>{{ $libur['hari'] }}</td>
                                <td>{{ $libur['keterangan'] }}</td>
                                <td>
                                    @if($libur['tipe'] === 'Libur Nasional')
                                        <span class="badge bg-primary">Libur Nasional</span>
                                    @else
                                        <span class="badge bg-secondary">Weekend</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <div class="action-btns justify-content-center">
                                        <button class="btn-action" title="Edit" onclick="alert('Edit {{ $libur['keterangan'] }}')">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn-action btn-delete" title="Hapus" onclick="confirm('Apakah Anda yakin ingin menghapus data {{ $libur['keterangan'] }}?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <div class="text-secondary small">Menampilkan 1 - 7 dari 7 data</div>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled"><span class="page-link">«</span></li>
                        <li class="page-item active"><span class="page-link bg-dark border-dark text-white">1</span></li>
                        <li class="page-item disabled"><span class="page-link">»</span></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>
@endsection
