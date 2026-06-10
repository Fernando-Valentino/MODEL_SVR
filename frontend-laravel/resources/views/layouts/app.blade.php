<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - PREPAR</title>
    <!-- Google Fonts: Inter (primary font) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- External Custom Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    
    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- External JavaScript -->
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>

    <!-- Sidebar component -->
    @include('layouts.sidebar')

    <!-- Topbar component -->
    @include('layouts.topbar')

    <!-- Main Content Area -->
    <main class="content-wrapper">
        <div class="content-header">
            <h1 class="page-title">@yield('title')</h1>
            @hasSection('subtitle')
                <p class="page-subtitle">@yield('subtitle')</p>
            @endif
        </div>

        <div class="content-body">
            @yield('content')
        </div>
    </main>

</body>
</html>
