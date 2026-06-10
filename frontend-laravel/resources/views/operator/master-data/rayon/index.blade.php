@extends('layouts.app')

@section('title', 'Data Rayon')
@section('subtitle', 'Halaman ini digunakan untuk mengelola data wilayah rayon parkir.')

@section('content')
<div class="container-fluid p-0">
    <!-- Toolbar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div class="d-flex gap-2 align-items-center">
            <div class="input-group" style="max-width: 240px;">
                <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                <input type="search" placeholder="Cari rayon..." class="form-control" />
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark" onclick="alert('Fitur Tambah Data Rayon akan diimplementasikan pada tahap berikutnya.')">
                <i class="bi bi-plus-lg me-1"></i> Tambah Data
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Daftar Data Rayon</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 65px;">No</th>
                            <th>Nama Rayon</th>
                            <th>Cakupan Wilayah</th>
                            <th>Karakteristik Area</th>
                            <th style="text-align: right;">Jumlah Juru Parkir</th>
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rayons as $rayon)
                            <tr>
                                <td>{{ $rayon['no'] }}</td>
                                <td style="font-weight: 600;">{{ $rayon['nama_rayon'] }}</td>
                                <td>{{ $rayon['cakupan_wilayah'] }}</td>
                                <td>{{ $rayon['karakteristik_area'] }}</td>
                                <td style="text-align: right;">{{ $rayon['jumlah_juru_parkir'] }}</td>
                                <td style="text-align: center;">
                                    <div class="action-btns justify-content-center">
                                        <button class="btn-action" title="Edit" onclick="alert('Edit {{ $rayon['nama_rayon'] }}')">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn-action btn-delete" title="Hapus" onclick="confirm('Apakah Anda yakin ingin menghapus data {{ $rayon['nama_rayon'] }}?')">
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
                <div class="text-secondary small">Menampilkan 1 - 5 dari 5 data</div>
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
