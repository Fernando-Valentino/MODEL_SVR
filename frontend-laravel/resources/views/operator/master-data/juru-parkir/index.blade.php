@extends('layouts.app')

@section('title', 'Data Juru Parkir')
@section('subtitle', 'Halaman ini digunakan untuk mengelola data juru parkir aktif berdasarkan rayon.')

@section('content')
<div class="container-fluid p-0">
    <!-- Toolbar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div class="d-flex gap-2 align-items-center">
            <div class="input-group" style="max-width: 240px;">
                <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                <input type="search" placeholder="Cari nama juru parkir..." class="form-control" />
            </div>
            <select class="form-select" style="max-width: 180px;">
                <option value="">Semua Rayon</option>
                <option value="1">Rayon I</option>
                <option value="2">Rayon II</option>
                <option value="3">Rayon III</option>
                <option value="4">Rayon IV</option>
                <option value="5">Rayon V</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark" onclick="alert('Fitur Tambah Data Juru Parkir akan diimplementasikan pada tahap berikutnya.')">
                <i class="bi bi-plus-lg me-1"></i> Tambah Data
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Daftar Data Juru Parkir</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 65px;">No</th>
                            <th>Nama Juru Parkir</th>
                            <th>Rayon</th>
                            <th style="width: 150px;">Status</th>
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($juruParkirs as $jukir)
                            <tr>
                                <td>{{ $jukir['no'] }}</td>
                                <td style="font-weight: 600;">{{ $jukir['nama'] }}</td>
                                <td>{{ $jukir['rayon'] }}</td>
                                <td>
                                    @if($jukir['status'] === 'Aktif')
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-danger">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <div class="action-btns justify-content-center">
                                        <button class="btn-action" title="Edit" onclick="alert('Edit {{ $jukir['nama'] }}')">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn-action btn-delete" title="Hapus" onclick="confirm('Apakah Anda yakin ingin menghapus data {{ $jukir['nama'] }}?')">
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
