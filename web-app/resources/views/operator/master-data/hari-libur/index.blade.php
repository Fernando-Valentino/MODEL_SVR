@extends('layouts.app')

@section('title', 'Data Hari Libur & Weekend')
@section('subtitle', 'Halaman ini digunakan untuk mengelola data hari libur nasional dan akhir pekan sebagai faktor eksternal dalam prediksi pendapatan.')

@section('content')
<div class="container-fluid p-0">
    <!-- Toolbar (Bootstrap Row / Col) -->
    <form method="GET" action="{{ route('operator.hari-libur.index') }}" class="mb-4">
        @if(request()->filled('search'))
            <input type="hidden" name="search" value="{{ request('search') }}" />
        @endif
        <div class="row align-items-center g-3">
            <!-- Left Side Filters -->
            <div class="col-12 col-md-7">
                <div class="row g-2 align-items-center">
                    <div class="col-sm-5 col-md-4">
                        <select name="year" id="yearFilter" class="form-select" onchange="this.form.submit()">
                            <option value="all" {{ request('year', date('Y')) == 'all' ? 'selected' : '' }}>Semua Tahun</option>
                            @foreach($availableYears as $yr)
                                <option value="{{ $yr }}" {{ request('year', date('Y')) == $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-5 col-md-4">
                        <select name="tipe" id="typeFilter" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Tipe</option>
                            <option value="Libur Nasional" {{ request('tipe') == 'Libur Nasional' ? 'selected' : '' }}>Libur Nasional</option>
                            <option value="Weekend" {{ request('tipe') == 'Weekend' ? 'selected' : '' }}>Weekend</option>
                        </select>
                    </div>
                    @if(request()->filled('year') || request()->filled('tipe'))
                        <div class="col-sm-2 col-md-2">
                            <a href="{{ route('operator.hari-libur.index', request()->only('search')) }}" class="btn btn-border w-100 justify-content-center" title="Reset Filter">
                                <i class="bi bi-x-circle me-1"></i> Reset
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Right Side Actions -->
            <div class="col-12 col-md-5 text-md-end">
                <div class="d-inline-flex gap-2">
                    <button type="button" class="btn btn-border" data-bs-toggle="modal" data-bs-target="#generateLiburModal">
                        <i class="bi bi-calendar-check me-1"></i> Generate Otomatis
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLiburModal">
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
                <h5 class="card-title mb-0" style="border-bottom: none !important; padding-bottom: 0 !important;">Daftar Data Hari Libur & Weekend</h5>
                <form method="GET" action="{{ route('operator.hari-libur.index') }}" class="d-flex gap-2 align-items-center m-0">
                    @if(request()->filled('year'))
                        <input type="hidden" name="year" value="{{ request('year') }}" />
                    @endif
                    @if(request()->filled('tipe'))
                        <input type="hidden" name="tipe" value="{{ request('tipe') }}" />
                    @endif
                    <div class="input-group" style="max-width: 280px;">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Cari keterangan..." class="form-control" />
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                    @if(request()->filled('search'))
                        <a href="{{ route('operator.hari-libur.index', request()->except('search')) }}" class="btn btn-border" title="Reset Pencarian">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </a>
                    @endif
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="liburTable">
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
                        @forelse($hariLiburs as $index => $libur)
                            <tr>
                                <td>{{ $index + 1 + ($hariLiburs->currentPage() - 1) * $hariLiburs->perPage() }}</td>
                                <td style="font-weight: 600;">{{ date('d-m-Y', strtotime($libur->tanggal)) }}</td>
                                <td>{{ $libur->hari }}</td>
                                <td>{{ $libur->keterangan }}</td>
                                <td>
                                    @if($libur->tipe === 'Libur Nasional')
                                        <span class="badge bg-primary">Libur Nasional</span>
                                    @else
                                        <span class="badge bg-secondary text-dark">Weekend</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <div class="action-btns justify-content-center">
                                        <button class="btn-action btn-edit" title="Edit" data-id="{{ $libur->id }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn-action btn-delete" title="Hapus" data-id="{{ $libur->id }}" data-desc="{{ $libur->keterangan }} ({{ date('d-m-Y', strtotime($libur->tanggal)) }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">Belum ada data hari libur.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <div class="text-secondary small">
                    Menampilkan {{ $hariLiburs->firstItem() ?? 0 }} - {{ $hariLiburs->lastItem() ?? 0 }} dari {{ $hariLiburs->total() }} data
                </div>
                <div>
                    {{ $hariLiburs->links('components.pagination') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Hari Libur -->
<div class="modal fade" id="addLiburModal" tabindex="-1" aria-labelledby="addLiburModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLiburModalLabel">Tambah Data Hari Libur / Weekend</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addLiburForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_tanggal" class="form-label">Tanggal</label>
                        <input type="date" class="form-control" id="add_tanggal" name="tanggal" required />
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="add_keterangan" class="form-label">Keterangan</label>
                        <input type="text" class="form-control" id="add_keterangan" name="keterangan" required placeholder="Contoh: Tahun Baru Imlek, Weekend" />
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="add_tipe" class="form-label">Tipe</label>
                        <select class="form-select" id="add_tipe" name="tipe" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="Libur Nasional">Libur Nasional</option>
                            <option value="Weekend">Weekend</option>
                        </select>
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

<!-- Modal Edit Hari Libur -->
<div class="modal fade" id="editLiburModal" tabindex="-1" aria-labelledby="editLiburModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLiburModalLabel">Edit Data Hari Libur / Weekend</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editLiburForm">
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
                        <label for="edit_keterangan" class="form-label">Keterangan</label>
                        <input type="text" class="form-control" id="edit_keterangan" name="keterangan" required />
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_tipe" class="form-label">Tipe</label>
                        <select class="form-select" id="edit_tipe" name="tipe" required>
                            <option value="Libur Nasional">Libur Nasional</option>
                            <option value="Weekend">Weekend</option>
                        </select>
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

<!-- Modal Generate Hari Libur / Weekend -->
<div class="modal fade" id="generateLiburModal" tabindex="-1" aria-labelledby="generateLiburModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateLiburModalLabel">Generate Hari Libur & Weekend</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="generateLiburForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3 text-center">
                        <label class="form-label">Pilih Tahun</label>
                        <div class="input-group justify-content-center mx-auto" style="max-width: 200px;">
                            <button class="btn btn-border px-3" type="button" id="btn-decrement-year">
                                <i class="bi bi-dash-lg"></i>
                            </button>
                            <input type="text" class="form-control text-center fw-bold" id="generate_year" name="year" value="{{ date('Y') }}" readonly style="background-color: var(--surface);" />
                            <button class="btn btn-border px-3" type="button" id="btn-increment-year">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback text-center" id="year-validation-feedback" style="font-size: 11.5px; font-weight: 500; margin-top: 8px;"></div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="import_api" name="import_api" value="1" checked />
                        <label class="form-check-label" for="import_api" style="font-size: 13px; font-weight: 500;">Ambil Hari Libur Nasional dari API</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="generate_weekend" name="generate_weekend" value="1" checked />
                        <label class="form-check-label" for="generate_weekend" style="font-size: 13px; font-weight: 500;">Generate Hari Sabtu & Minggu (Weekend)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitGenerate">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Year Increment/Decrement
        const yearInput = document.getElementById('generate_year');
        const btnDec = document.getElementById('btn-decrement-year');
        const btnInc = document.getElementById('btn-increment-year');

        if (btnDec && btnInc && yearInput) {
            btnDec.addEventListener('click', function() {
                let currentYear = parseInt(yearInput.value);
                if (currentYear > 2020) {
                    yearInput.value = currentYear - 1;
                }
            });

            btnInc.addEventListener('click', function() {
                let currentYear = parseInt(yearInput.value);
                if (currentYear < 2035) {
                    yearInput.value = currentYear + 1;
                }
            });
        }

        // Generate Form AJAX
        const generateForm = document.getElementById('generateLiburForm');
        const btnSubmitGenerate = document.getElementById('btnSubmitGenerate');

        generateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            clearValidations(generateForm);
            btnSubmitGenerate.disabled = true;
            btnSubmitGenerate.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...';

            const formData = new FormData(generateForm);

            fetch("{{ route('operator.hari-libur.generate') }}", {
                method: "POST",
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                btnSubmitGenerate.disabled = false;
                btnSubmitGenerate.innerHTML = 'Generate';

                if (res.status === 422) {
                    return res.json().then(data => {
                        Object.keys(data.errors).forEach(key => {
                            const input = generateForm.querySelector(`[name="${key}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                if (key === 'year') {
                                    const feedback = document.getElementById('year-validation-feedback');
                                    if (feedback) {
                                        feedback.textContent = data.errors[key][0];
                                        feedback.style.display = 'block';
                                    }
                                } else {
                                    const feedback = input.parentNode.querySelector('.invalid-feedback');
                                    if (feedback) {
                                        feedback.textContent = data.errors[key][0];
                                    }
                                }
                            }
                        });
                        throw new Error("Validation Error");
                    });
                }

                if (!res.ok) {
                    return res.json().then(data => {
                        throw new Error(data.message || 'Terjadi kesalahan saat generate data.');
                    });
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    const modalEl = document.getElementById('generateLiburModal');
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    generateForm.reset();
                    showToast('success', data.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            })
            .catch(err => {
                btnSubmitGenerate.disabled = false;
                btnSubmitGenerate.innerHTML = 'Generate';
                if (err.message !== "Validation Error") {
                    Swal.fire('Gagal!', err.message, 'error');
                }
            });
        });

        // Clear validations helper
        function clearValidations(form) {
            form.querySelectorAll('.form-control, .form-select').forEach(input => {
                input.classList.remove('is-invalid');
            });
            form.querySelectorAll('.invalid-feedback').forEach(div => {
                div.textContent = '';
                div.style.display = 'none';
            });
        }

        // Add Form AJAX
        const addForm = document.getElementById('addLiburForm');
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            clearValidations(addForm);

            const formData = new FormData(addForm);

            fetch("{{ route('operator.hari-libur.store') }}", {
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
                    const modalEl = document.getElementById('addLiburModal');
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
                const form = document.getElementById('editLiburForm');
                clearValidations(form);

                fetch(`/operator/master-data/hari-libur/${id}`)
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById('edit_id').value = data.id;
                        document.getElementById('edit_tanggal').value = data.tanggal;
                        document.getElementById('edit_keterangan').value = data.keterangan;
                        document.getElementById('edit_tipe').value = data.tipe;
                        
                        const modalEl = document.getElementById('editLiburModal');
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                    });
            });
        });

        // Edit Form AJAX
        const editForm = document.getElementById('editLiburForm');
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            clearValidations(editForm);

            const id = document.getElementById('edit_id').value;
            const formData = new FormData(editForm);

            fetch(`/operator/master-data/hari-libur/${id}`, {
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
                    const modalEl = document.getElementById('editLiburModal');
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
                    text: `Hari libur "${desc}" akan dihapus!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#005BAA',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/operator/master-data/hari-libur/${id}`, {
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
    });
</script>
@endsection
