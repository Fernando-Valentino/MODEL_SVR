@extends('layouts.app')

@section('title', 'Data Pendapatan')
@section('subtitle', 'Halaman ini digunakan untuk mengelola data pendapatan retribusi parkir harian.')

@section('content')
<div class="container-fluid p-0">
    <!-- Toolbar (Bootstrap Row / Flex) -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div class="d-flex gap-2 align-items-center">
            <div class="input-group" style="max-width: 240px;">
                <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                <input type="search" placeholder="Cari data..." class="form-control" />
            </div>
            <input type="date" class="form-control" title="Filter Tanggal" style="max-width: 180px;" />
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark" onclick="alert('Fitur Import Excel akan diimplementasikan pada tahap berikutnya.')">
                <i class="bi bi-file-earmark-excel me-1"></i> Import Excel
            </button>
            <button class="btn btn-dark" onclick="alert('Fitur Tambah Data Pendapatan akan diimplementasikan pada tahap berikutnya.')">
                <i class="bi bi-plus-lg me-1"></i> Tambah Data
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Daftar Data Pendapatan</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 65px;">No</th>
                            <th>Tanggal</th>
                            <th>Rayon</th>
                            <th>Juru Parkir</th>
                            <th style="text-align: right;">Jumlah Pendapatan</th>
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendapatans as $income)
                            <tr>
                                <td>{{ $income['no'] }}</td>
                                <td>{{ $income['tanggal'] }}</td>
                                <td>{{ $income['rayon'] }}</td>
                                <td>{{ $income['juru_parkir'] }}</td>
                                <td style="text-align: right; font-weight: 600;">{{ $income['jumlah'] }}</td>
                                <td style="text-align: center;">
                                    <div class="action-btns justify-content-center">
                                        <button class="btn-action" title="Edit" onclick="alert('Edit data pendapatan no {{ $income['no'] }}')">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn-action btn-delete" title="Hapus" onclick="confirm('Apakah Anda yakin ingin menghapus data pendapatan ini?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination (Bootstrap Style) -->
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
