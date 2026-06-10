@extends('layouts.app')

@section('title', 'Data Pendapatan')
@section('subtitle', 'Halaman ini digunakan untuk mengelola data pendapatan retribusi parkir harian.')

@section('content')
<div class="container-fluid p-0">
    <!-- Toolbar (Bootstrap Row / Col) -->
    <form method="GET" action="{{ route('operator.pendapatan.index') }}" class="mb-4">
        @if(request()->filled('search'))
            <input type="hidden" name="search" value="{{ request('search') }}" />
        @endif
        <div class="row align-items-center g-3">
            <!-- Left Side Filters -->
            <div class="col-12 col-md-8">
                <div class="row g-2 align-items-center">
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white px-2" style="font-size: 12px; font-weight: 500;">Mulai</span>
                            <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="form-control px-2" title="Tanggal Mulai" onchange="this.form.submit()" />
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white px-2" style="font-size: 12px; font-weight: 500;">Selesai</span>
                            <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}" class="form-control px-2" title="Tanggal Selesai" onchange="this.form.submit()" />
                        </div>
                    </div>
                    <div class="col-sm-5 col-md-4 col-lg-3">
                        <select name="rayon_id" class="form-select text-truncate" onchange="this.form.submit()">
                            <option value="">Semua Rayon</option>
                            @foreach($rayons as $rayon)
                                <option value="{{ $rayon->id }}" {{ request('rayon_id') == $rayon->id ? 'selected' : '' }}>
                                    {{ $rayon->nama_rayon }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if(request()->filled('tanggal_mulai') || request()->filled('tanggal_selesai') || request()->filled('rayon_id'))
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <a href="{{ route('operator.pendapatan.index', request()->only('search')) }}" class="btn btn-border w-100 justify-content-center" title="Reset Filter">
                                <i class="bi bi-x-circle me-1"></i> Reset
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Right Side Actions -->
            <div class="col-12 col-md-4 text-md-end">
                <div class="d-inline-flex gap-2">
                    <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#importPendapatanModal">
                        <i class="bi bi-file-earmark-excel me-1"></i> Import Excel/CSV
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPendapatanModal">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Data
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Table Card -->
    <div class="card">
        <div class="card-body">
            <!-- Table Header Toolbar -->
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom flex-wrap gap-3">
                <h5 class="card-title mb-0" style="border-bottom: none !important; padding-bottom: 0 !important;">Daftar Data Pendapatan</h5>
                <form method="GET" action="{{ route('operator.pendapatan.index') }}" class="d-flex gap-2 align-items-center m-0">
                    @if(request()->filled('tanggal_mulai'))
                        <input type="hidden" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" />
                    @endif
                    @if(request()->filled('tanggal_selesai'))
                        <input type="hidden" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}" />
                    @endif
                    @if(request()->filled('rayon_id'))
                        <input type="hidden" name="rayon_id" value="{{ request('rayon_id') }}" />
                    @endif
                    <div class="input-group" style="max-width: 280px;">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Cari nama rayon..." class="form-control" />
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                    @if(request()->filled('search'))
                        <a href="{{ route('operator.pendapatan.index', request()->except('search')) }}" class="btn btn-border" title="Reset Pencarian">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </a>
                    @endif
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="pendapatanTable">
                    <thead>
                        <tr>
                            <th style="width: 65px;">No</th>
                            <th>Tanggal</th>
                            <th>Rayon</th>
                            <th>Jumlah Juru Parkir</th>
                            <th style="text-align: right;">Jumlah Pendapatan</th>
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendapatans as $index => $income)
                            <tr>
                                <td>{{ $index + 1 + ($pendapatans->currentPage() - 1) * $pendapatans->perPage() }}</td>
                                <td>{{ date('d-m-Y', strtotime($income->tanggal)) }}</td>
                                <td style="font-weight: 500;">{{ $income->rayon->nama_rayon ?? 'Tidak Diketahui' }}</td>
                                <td>{{ $income->juruParkir->jumlah_juru_parkir ?? ($income->rayon->jumlah_juru_parkir ?? 80) }} Jukir</td>
                                <td style="text-align: right; font-weight: 600; color: var(--primary-blue);">
                                    Rp {{ number_format($income->jumlah, 0, ',', '.') }}
                                </td>
                                <td style="text-align: center;">
                                    <div class="action-btns justify-content-center">
                                        <button class="btn-action btn-edit" title="Edit" data-id="{{ $income->id }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn-action btn-delete" title="Hapus" data-id="{{ $income->id }}" data-desc="Rayon {{ $income->rayon->nama_rayon ?? '' }} ({{ date('d-m-Y', strtotime($income->tanggal)) }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">Belum ada data pendapatan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <div class="text-secondary small">
                    Menampilkan {{ $pendapatans->firstItem() ?? 0 }} - {{ $pendapatans->lastItem() ?? 0 }} dari {{ $pendapatans->total() }} data
                </div>
                <div>
                    {{ $pendapatans->links('components.pagination') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Pendapatan -->
<div class="modal fade" id="addPendapatanModal" tabindex="-1" aria-labelledby="addPendapatanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPendapatanModalLabel">Tambah Data Pendapatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addPendapatanForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_tanggal" class="form-label">Tanggal</label>
                        <input type="date" class="form-control" id="add_tanggal" name="tanggal" required />
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="add_rayon_id" class="form-label">Rayon</label>
                        <select class="form-select" id="add_rayon_id" name="rayon_id" required>
                            <option value="">-- Pilih Rayon --</option>
                            @foreach($rayons as $rayon)
                                <option value="{{ $rayon->id }}">{{ $rayon->nama_rayon }} ({{ $rayon->kecamatan }})</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="add_jumlah" class="form-label">Jumlah Pendapatan (Rp)</label>
                        <input type="number" class="form-control" id="add_jumlah" name="jumlah" required min="0" placeholder="Contoh: 1250000" />
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Pendapatan -->
<div class="modal fade" id="editPendapatanModal" tabindex="-1" aria-labelledby="editPendapatanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPendapatanModalLabel">Edit Data Pendapatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPendapatanForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id" name="id" />
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_tanggal" class="form-label">Tanggal</label>
                        <input type="date" class="form-control" id="edit_tanggal" name="tanggal" required />
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_rayon_id" class="form-label">Rayon</label>
                        <select class="form-select" id="edit_rayon_id" name="rayon_id" required>
                            @foreach($rayons as $rayon)
                                <option value="{{ $rayon->id }}">{{ $rayon->nama_rayon }} ({{ $rayon->kecamatan }})</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_jumlah" class="form-label">Jumlah Pendapatan (Rp)</label>
                        <input type="number" class="form-control" id="edit_jumlah" name="jumlah" required min="0" />
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import Pendapatan -->
<div class="modal fade" id="importPendapatanModal" tabindex="-1" aria-labelledby="importPendapatanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importPendapatanModalLabel">Import Data Pendapatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importPendapatanForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <!-- Guidelines / Sample Data -->
                    <div class="bg-light p-2 rounded border mb-3" style="font-size: 11px;">
                        <span class="d-block fw-bold text-secondary mb-1" style="font-size: 11px;">Format File CSV / Excel:</span>
                        <div class="table-responsive">
                            <table class="table table-bordered bg-white text-dark mb-1 font-monospace" style="font-size: 10px; margin-bottom: 0;">
                                <thead>
                                    <tr class="table-light">
                                        <th style="padding: 4px 6px !important; text-align: center;">Tanggal</th>
                                        <th style="padding: 4px 6px !important; text-align: center;">Rayon</th>
                                        <th style="padding: 4px 6px !important; text-align: center;">Jumlah Jukir</th>
                                        <th style="padding: 4px 6px !important; text-align: center;">Total Pendapatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 4px 6px !important; text-align: center;">2026-06-01</td>
                                        <td style="padding: 4px 6px !important; text-align: center;">Rayon I</td>
                                        <td style="padding: 4px 6px !important; text-align: center;">80</td>
                                        <td style="padding: 4px 6px !important; text-align: center;">1250000</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 4px 6px !important; text-align: center;">2026-06-01</td>
                                        <td style="padding: 4px 6px !important; text-align: center;">Rayon II</td>
                                        <td style="padding: 4px 6px !important; text-align: center;">82</td>
                                        <td style="padding: 4px 6px !important; text-align: center;">1680000</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <small class="d-block text-muted mt-1" style="font-size: 9px; line-height: 1.3;">
                            * Kolom <strong>Jumlah Jukir</strong> opsional. Kolom <strong>Rayon</strong> bisa berupa teks (Rayon I) atau ID (1).
                        </small>
                    </div>

                    <a href="{{ route('operator.pendapatan.template') }}" class="btn btn-outline-secondary w-100 mb-3">
                        <i class="bi bi-download me-1"></i> Unduh Template CSV
                    </a>

                    <div class="mb-3">
                        <label for="import_file" class="form-label" style="font-size: 13px; font-weight: 600;">Pilih File CSV (.csv)</label>
                        <input type="file" class="form-control" id="import_file" name="file" accept=".csv" required />
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitImport">Unggah & Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Clear validations helper
        function clearValidations(form) {
            form.querySelectorAll('.form-control, .form-select').forEach(input => {
                input.classList.remove('is-invalid');
            });
            form.querySelectorAll('.invalid-feedback').forEach(div => {
                div.textContent = '';
            });
        }

        // Add Form AJAX
        const addForm = document.getElementById('addPendapatanForm');
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            clearValidations(addForm);

            const formData = new FormData(addForm);

            fetch("{{ route('operator.pendapatan.store') }}", {
                method: "POST",
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (res.status === 422) {
                    return res.json().then(data => {
                        Object.keys(data.errors).forEach(key => {
                            const input = addForm.querySelector(`[name="${key}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                const feedback = input.nextElementSibling;
                                if (feedback && feedback.classList.contains('invalid-feedback')) {
                                    feedback.textContent = data.errors[key][0];
                                }
                            }
                        });
                        throw new Error("Validation Error");
                    });
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    const modalEl = document.getElementById('addPendapatanModal');
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    addForm.reset();
                    showToast('success', data.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(err => {
                if (err.message !== "Validation Error") {
                    Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                }
            });
        });

        // Load Edit Modal
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const form = document.getElementById('editPendapatanForm');
                clearValidations(form);

                fetch(`/operator/master-data/pendapatan/${id}`)
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById('edit_id').value = data.id;
                        document.getElementById('edit_tanggal').value = data.tanggal;
                        document.getElementById('edit_rayon_id').value = data.rayon_id;
                        document.getElementById('edit_jumlah').value = data.jumlah;
                        
                        const modalEl = document.getElementById('editPendapatanModal');
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                    });
            });
        });

        // Edit Form AJAX
        const editForm = document.getElementById('editPendapatanForm');
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            clearValidations(editForm);

            const id = document.getElementById('edit_id').value;
            const formData = new FormData(editForm);

            fetch(`/operator/master-data/pendapatan/${id}`, {
                method: "POST",
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (res.status === 422) {
                    return res.json().then(data => {
                        Object.keys(data.errors).forEach(key => {
                            const input = editForm.querySelector(`[name="${key}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                const feedback = input.nextElementSibling;
                                if (feedback && feedback.classList.contains('invalid-feedback')) {
                                    feedback.textContent = data.errors[key][0];
                                }
                            }
                        });
                        throw new Error("Validation Error");
                    });
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    const modalEl = document.getElementById('editPendapatanModal');
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    showToast('success', data.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(err => {
                if (err.message !== "Validation Error") {
                    Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                }
            });
        });

        // Delete Confirmation
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const desc = this.getAttribute('data-desc');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: `Data pendapatan untuk "${desc}" akan dihapus!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#005BAA',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/operator/master-data/pendapatan/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => {
                            if (!res.ok) {
                                return res.json().then(data => {
                                    throw new Error(data.message || 'Gagal menghapus data.');
                                });
                            }
                            return res.json();
                        })
                        .then(data => {
                            if (data.success) {
                                showToast('success', data.message);
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            }
                        })
                        .catch(err => {
                            Swal.fire('Gagal!', err.message, 'error');
                        });
                    }
                });
            });
        });

        // Import Form AJAX
        const importForm = document.getElementById('importPendapatanForm');
        const btnSubmitImport = document.getElementById('btnSubmitImport');

        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            clearValidations(importForm);

            // Change button state
            btnSubmitImport.disabled = true;
            btnSubmitImport.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Mengimpor...';

            const formData = new FormData(importForm);

            fetch("{{ route('operator.pendapatan.import') }}", {
                method: "POST",
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                btnSubmitImport.disabled = false;
                btnSubmitImport.innerHTML = 'Unggah & Import';

                if (res.status === 422) {
                    return res.json().then(data => {
                        if (data.errors && Array.isArray(data.errors)) {
                            // Show detailed parser validation list
                            Swal.fire({
                                title: 'Gagal Import Data',
                                html: '<div class="text-start" style="max-height:200px; overflow-y:auto;"><ul class="text-danger">' + 
                                      data.errors.map(err => `<li>${err}</li>`).join('') + 
                                      '</ul></div>',
                                icon: 'error'
                            });
                        } else {
                            Swal.fire('Gagal!', data.message || 'Format file salah.', 'error');
                        }
                        throw new Error("Validation Error");
                    });
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    const modalEl = document.getElementById('importPendapatanModal');
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    importForm.reset();

                    let messageText = data.message;
                    if (data.warnings && data.warnings.length > 0) {
                        messageText += ` Ada beberapa baris yang di-skip karena kesalahan data.`;
                    }

                    showToast('success', messageText);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(err => {
                btnSubmitImport.disabled = false;
                btnSubmitImport.innerHTML = 'Unggah & Import';
                if (err.message !== "Validation Error") {
                    Swal.fire('Error', 'Terjadi kesalahan sistem atau format file tidak sesuai.', 'error');
                }
            });
        });
    });
</script>
@endsection
