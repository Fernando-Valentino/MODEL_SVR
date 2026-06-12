@php
    $routeName = request()->route()->getName();

    // Cek apakah halaman aktif ada di dalam grup Master Data
    $masterDataActive = Str::startsWith($routeName, [
        'operator.pendapatan',
        'operator.rayon',
        'operator.juru-parkir',
        'operator.hari-libur',
    ]);
@endphp

<div class="sidebar">
    <div class="sidebar-brand">
        <h2>PREPAR</h2>
        <p>Dishub Kota Cirebon</p>
    </div>

    <nav class="sidebar-menu">
        @hasrole('operator')
            <!-- MENU OPERATOR UPT PARKIR -->
            <a href="{{ route('operator.dashboard') }}" class="menu-item {{ Str::startsWith($routeName, 'operator.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill menu-icon"></i> Dashboard
            </a>

            {{-- ===== MASTER DATA DROPDOWN ===== --}}
            <div class="dropdown-menu-group {{ $masterDataActive ? 'open' : '' }}">
                {{-- Trigger / Header --}}
                <button class="menu-item dropdown-trigger w-100 {{ $masterDataActive ? 'active-parent' : '' }}"
                        onclick="toggleDropdown(this)">
                    <i class="bi bi-database-fill menu-icon"></i>
                    <span class="flex-grow-1 text-start">Master Data Retribusi</span>
                    <i class="bi bi-chevron-down dropdown-chevron"></i>
                </button>

                {{-- Sub-menu items --}}
                <div class="dropdown-submenu">
                    <a href="{{ route('operator.pendapatan.index') }}"
                       class="menu-item sub-item {{ Str::startsWith($routeName, 'operator.pendapatan') ? 'active' : '' }}">
                        <i class="bi bi-wallet2 menu-icon"></i> Data Pendapatan
                    </a>
                    <a href="{{ route('operator.rayon.index') }}"
                       class="menu-item sub-item {{ Str::startsWith($routeName, 'operator.rayon') ? 'active' : '' }}">
                        <i class="bi bi-geo-alt-fill menu-icon"></i> Data Rayon
                    </a>
                    <a href="{{ route('operator.juru-parkir.index') }}"
                       class="menu-item sub-item {{ Str::startsWith($routeName, 'operator.juru-parkir') ? 'active' : '' }}">
                        <i class="bi bi-people-fill menu-icon"></i> Data Juru Parkir
                    </a>
                    <a href="{{ route('operator.hari-libur.index') }}"
                       class="menu-item sub-item {{ Str::startsWith($routeName, 'operator.hari-libur') ? 'active' : '' }}">
                        <i class="bi bi-calendar-event menu-icon"></i> Hari Libur & Weekend
                    </a>
                </div>
            </div>
            {{-- ===== END MASTER DATA DROPDOWN ===== --}}

            <a href="{{ route('operator.prediksi.index') }}" class="menu-item {{ Str::startsWith($routeName, 'operator.prediksi') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow menu-icon"></i> Kelola Model Prediksi
            </a>

            <a href="{{ route('operator.optimasi.index') }}" class="menu-item {{ Str::startsWith($routeName, 'operator.optimasi') ? 'active' : '' }}">
                <i class="bi bi-sliders menu-icon"></i> Optimasi Parameter
            </a>

            <a href="{{ route('operator.laporan.index') }}" class="menu-item {{ Str::startsWith($routeName, 'operator.laporan') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph menu-icon"></i> Laporan Prediksi
            </a>
        @endhasrole

        @hasrole('kepala_upt')
            <!-- MENU KEPALA UPT PARKIR -->
            <a href="{{ route('kepala-upt.dashboard') }}" class="menu-item {{ Str::startsWith($routeName, 'kepala-upt.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill menu-icon"></i> Dashboard
            </a>

            <a href="{{ route('kepala-upt.prediksi.index') }}" class="menu-item {{ Str::startsWith($routeName, 'kepala-upt.prediksi') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow menu-icon"></i> Kelola Model Prediksi
            </a>

            <a href="{{ route('kepala-upt.optimasi.index') }}" class="menu-item {{ Str::startsWith($routeName, 'kepala-upt.optimasi') ? 'active' : '' }}">
                <i class="bi bi-sliders menu-icon"></i> Optimasi Parameter
            </a>

            <a href="{{ route('kepala-upt.laporan.index') }}" class="menu-item {{ Str::startsWith($routeName, 'kepala-upt.laporan') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph menu-icon"></i> Laporan Prediksi
            </a>
        @endhasrole

        @hasrole('kepala_dishub')
            <!-- MENU KEPALA DISHUB -->
            <a href="{{ route('kepala-dishub.dashboard') }}" class="menu-item {{ Str::startsWith($routeName, 'kepala-dishub.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill menu-icon"></i> Dashboard
            </a>

            <a href="{{ route('kepala-dishub.prediksi.index') }}" class="menu-item {{ Str::startsWith($routeName, 'kepala-dishub.prediksi') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow menu-icon"></i> Kelola Model Prediksi
            </a>

            <a href="{{ route('kepala-dishub.optimasi.index') }}" class="menu-item {{ Str::startsWith($routeName, 'kepala-dishub.optimasi') ? 'active' : '' }}">
                <i class="bi bi-sliders menu-icon"></i> Optimasi Parameter
            </a>

            <a href="{{ route('kepala-dishub.laporan.index') }}" class="menu-item {{ Str::startsWith($routeName, 'kepala-dishub.laporan') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph menu-icon"></i> Laporan Prediksi
            </a>
        @endhasrole
    </nav>

    <!-- Logout Section at bottom -->
    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}" id="logoutForm">
            @csrf
            <button type="button" class="btn-logout" id="btnLogout" onclick="konfirmasiLogout()">
                <i class="bi bi-box-arrow-right"></i> Keluar Sistem
            </button>
        </form>
    </div>
</div>

<script>
    function toggleDropdown(btn) {
        const group = btn.closest('.dropdown-menu-group');
        group.classList.toggle('open');
    }

    // Auto-open on page load jika ada child yang active
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.dropdown-menu-group.open').forEach(function (group) {
            // sudah di-handle dari server-side class 'open'
        });
    });

    function konfirmasiLogout() {
        if (typeof Swal === 'undefined') {
            // Fallback jika SweetAlert2 belum dimuat
            if (confirm('Apakah Anda yakin ingin keluar dari sistem?')) {
                document.getElementById('logoutForm').submit();
            }
            return;
        }

        Swal.fire({
            title: 'Keluar dari Sistem?',
            text: 'Anda akan dikeluarkan dari sesi aktif ini.',
            icon: 'question',
            iconColor: '#005BAA',
            showCancelButton: true,
            confirmButtonColor: '#005BAA',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i> Ya, Keluar',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                confirmButton: 'swal-confirm-btn',
                cancelButton: 'swal-cancel-btn'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('logoutForm').submit();
            }
        });
    }
</script>
