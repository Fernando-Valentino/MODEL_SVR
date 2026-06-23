<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem - REVORA</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #005BAA;
            --primary-blue-dark: #003B73;
            --primary-blue-light: #EAF4FF;
            --border: #D9E2EC;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F5F7FA;
            color: #1F2937;
        }

        .login-card {
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0, 91, 170, 0.06), 0 10px 24px rgba(0, 91, 170, 0.04);
            background-color: #ffffff;
            padding: 40px;
        }

        /* Logo box - biru Dishub */
        .logo-placeholder {
            width: 52px;
            height: 52px;
            background-color: var(--primary-blue);
            border-radius: 10px;
            margin: 0 auto 12px auto;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 22px;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        /* Nama aplikasi */
        .app-name {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 22px;
            color: #1F2937;
            letter-spacing: -0.3px;
        }

        /* Label sub-judul biru */
        .app-subtitle {
            color: var(--primary-blue);
            font-size: 13px;
            font-weight: 500;
        }

        /* "LOGIN SISTEM" heading */
        .login-heading {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--primary-blue-dark);
        }

        /* Input group icons */
        .input-group-text {
            background-color: #ffffff;
            cursor: pointer;
            border-color: var(--border);
        }

        /* Form control base */
        .form-control {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            border-color: var(--border);
            color: #1F2937;
        }

        /* Focus ring - biru */
        .form-control:focus {
            border-color: var(--primary-blue) !important;
            box-shadow: 0 0 0 3px rgba(0, 91, 170, 0.12) !important;
            outline: none;
        }

        /* Checkbox accent */
        .form-check-input:checked {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(0, 91, 170, 0.12);
        }

        /* Tombol login - biru utama */
        .btn-submit {
            background-color: var(--primary-blue) !important;
            border-color: var(--primary-blue) !important;
            color: #ffffff !important;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.2px;
            transition: all 0.2s ease;
            border-radius: 8px;
        }

        .btn-submit:hover {
            background-color: var(--primary-blue-dark) !important;
            border-color: var(--primary-blue-dark) !important;
        }

        /* Lupa password link */
        .link-forgot {
            color: var(--primary-blue);
            font-size: 12.5px;
            text-decoration: none;
        }

        .link-forgot:hover {
            text-decoration: underline;
            color: var(--primary-blue-dark);
        }

        /* Divider border bawah header */
        .header-divider {
            border-color: var(--border) !important;
        }
    </style>
</head>
<body class="d-flex min-vh-100 flex-column align-items-center justify-content-center py-4">

    <div class="w-100" style="max-width: 440px;">
        <div class="login-card mb-4">
            <!-- Header Logo and Titles -->
            <div class="text-center mb-4 pb-4 border-bottom">
                <div class="logo-placeholder">R</div>
                <div class="app-name mb-1">REVORA</div>
                <div class="app-subtitle">Sistem <strong>Prediksi</strong> Pendapatan Retribusi Parkir</div>
                <div class="text-uppercase text-secondary fw-semibold mt-1" style="font-size: 10px; letter-spacing: 0.5px;">Dinas Perhubungan Kota Cirebon</div>
            </div>

            <div class="text-center login-heading mb-4">Login Sistem</div>

            <!-- Errors display -->
            @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-center py-2 px-3 mb-3 border-danger-subtle rounded-2" role="alert" style="font-size: 12.5px;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Username -->
                <div class="mb-3">
                    <label for="username" class="form-label small fw-semibold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-person text-secondary"></i></span>
                        <input type="text" id="username" name="username" class="form-control border-start-0" placeholder="Masukkan username" value="{{ old('username') }}" required autofocus autocomplete="username">
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="form-label small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock text-secondary"></i></span>
                        <input type="password" id="password" name="password" class="form-control border-start-0 border-end-0" placeholder="Masukkan password" required autocomplete="current-password">
                        <span class="input-group-text" id="togglePassword" title="Lihat password"><i class="bi bi-eye text-secondary"></i></span>
                    </div>
                </div>

                <!-- Checkbox and Forgot Password -->
                <div class="d-flex justify-content-between align-items-center mb-4" style="font-size: 12.5px;">
                    <div class="form-check">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label text-secondary" for="remember">Ingat saya</label>
                    </div>
                    <a href="#" class="link-forgot">Lupa password?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-submit w-100 py-2">Login</button>
            </form>
        </div>

        <!-- Footer Notes -->
        <div class="text-center text-secondary" style="font-size: 11px; line-height: 1.6;">
            <div class="fw-semibold text-dark mb-1">Gunakan akun sesuai hak akses pengguna.</div>
            <div>Sistem Prediksi Pendapatan Retribusi Parkir</div>
            <div>&copy; Dinas Perhubungan Kota Cirebon</div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const eyeIcon = togglePassword.querySelector('i');

        togglePassword.addEventListener('click', function (e) {
            // toggle type
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // toggle eye icon
            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>
