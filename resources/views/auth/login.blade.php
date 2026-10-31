<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>Masuk | Choplug Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
<main class="login-layout">
    <section class="login-brand-panel" aria-label="Choplug Portal">
        <div class="login-brand-content">
            <img class="login-brand-logo" src="{{ asset('images/choplug.jpeg') }}" alt="Logo Choplug">
            <div class="login-brand-copy"><p class="login-eyebrow">CHOPLUG PORTAL</p><h1>Pengelolaan kas dan iuran anggota.</h1><p>Masuk untuk melanjutkan ke ruang administrasi Choplug.</p></div>
        </div>
        <div class="login-brand-footer">Choplug Portal <span>·</span> {{ now()->format('Y') }}</div>
    </section>
    <section class="login-form-panel">
        <div class="login-card">
            <div class="login-card-heading"><span class="login-card-icon"><i class="bi bi-person-lock"></i></span><div><h2>Selamat datang</h2><p>Masuk ke akun Anda untuk melanjutkan.</p></div></div>
            @if($errors->any())<div class="alert alert-danger py-2" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label" for="email">Email</label><div class="input-group"><span class="input-group-text"><i class="bi bi-envelope"></i></span><input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="nama@email.com" required autofocus></div></div>
                <div class="mb-3"><label class="form-label" for="password">Kata sandi</label><div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span><input class="form-control" id="password" type="password" name="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required></div></div>
                <div class="d-flex justify-content-between align-items-center mb-4"><label class="form-check mb-0"><input class="form-check-input" type="checkbox" name="remember" value="1"><span class="form-check-label">Ingat saya</span></label></div>
                <button class="btn btn-primary w-100 login-submit" type="submit">Masuk <i class="bi bi-arrow-right ms-1"></i></button>
            </form>
            <p class="login-secure-note"><i class="bi bi-shield-lock me-1"></i>Akses hanya untuk pengguna terdaftar.</p>
        </div>
        <div class="login-mobile-brand"><img src="{{ asset('images/choplug.jpeg') }}" alt=""><span>Choplug Portal</span></div>
    </section>
</main>
</body>
</html>