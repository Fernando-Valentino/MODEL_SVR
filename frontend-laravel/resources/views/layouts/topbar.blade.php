@php
    $user = auth()->user();
    
    // Map Spatie roles to formal display names
    $roleDisplay = 'Pengguna, Viewer Access';
    if ($user) {
        if ($user->hasRole('operator')) {
            $roleDisplay = 'Operator UPT Parkir';
        } elseif ($user->hasRole('kepala_upt')) {
            $roleDisplay = 'Kepala UPT Parkir, Viewer Access';
        } elseif ($user->hasRole('kepala_dishub')) {
            $roleDisplay = 'Kepala Dishub, Viewer Access';
        }
    }

    $initial = strtoupper(substr($user->username ?? 'U', 0, 1));
@endphp

<header class="topbar">
    <div class="topbar-left">
        <span class="system-status">● Sistem Aktif</span>
    </div>
    
    <div class="topbar-right">
        <div class="user-info">
            <span class="user-role">{{ $roleDisplay }}</span>
            <span class="user-name">{{ $user->username ?? 'Guest' }}</span>
        </div>
        
        <div class="user-avatar" title="Profil Pengguna">
            {{ $initial }}
        </div>
    </div>
</header>
