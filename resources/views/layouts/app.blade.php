<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title ?? 'Dashboard' }} | Choplug Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-open">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand border-bottom">
        <div class="container-fluid"><ul class="navbar-nav align-items-center"><li class="nav-item"><a class="nav-link header-menu-button" data-lte-toggle="sidebar" href="#" aria-label="Buka navigasi"><i class="bi bi-list"></i></a></li><li class="nav-item d-none d-md-block"><span class="nav-link header-app-name">Choplug Portal</span></li></ul><ul class="navbar-nav ms-auto align-items-center"><li class="nav-item"><span class="nav-link header-date"><i class="bi bi-calendar3 me-2"></i>{{ now()->translatedFormat('l, d F Y') }}</span></li><li class="nav-item"><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-link nav-link" type="submit" aria-label="Keluar" title="Keluar"><i class="bi bi-box-arrow-right"></i></button></form></li></ul></div>
    </nav>
    <aside class="app-sidebar shadow" data-bs-theme="dark">
        <div class="sidebar-brand"><a href="{{ route('dashboard') }}" class="brand-link"><img src="{{ asset('images/choplug.jpeg') }}" alt="Logo Choplug Portal" class="brand-image opacity-100 rounded" style="width:33px;height:33px;object-fit:cover"><span class="brand-text fw-semibold">Choplug Portal</span></a></div>
        <div class="sidebar-wrapper"><nav class="mt-3"><ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu"><li class="nav-header text-uppercase">Menu</li><li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link @if(request()->routeIs('dashboard')) active @endif"><i class="nav-icon bi bi-grid-1x2"></i><p>Ringkasan</p></a></li><li class="nav-item"><a href="{{ route('members.index') }}" class="nav-link @if(request()->routeIs('members.index')) active @endif"><i class="nav-icon bi bi-people"></i><p>Anggota</p></a></li><li class="nav-item"><a href="{{ route('members.cards') }}" class="nav-link @if(request()->routeIs('members.cards', 'members.card')) active @endif"><i class="nav-icon bi bi-card-heading"></i><p>Kartu Anggota</p></a></li><li class="nav-item"><a href="{{ route('payments.index') }}" class="nav-link @if(request()->routeIs('payments.index')) active @endif"><i class="nav-icon bi bi-receipt"></i><p>Riwayat pembayaran</p></a></li><li class="nav-item"><a href="{{ route('transactions.index') }}" class="nav-link @if(request()->routeIs('transactions.*')) active @endif"><i class="nav-icon bi bi-arrow-left-right"></i><p>Transaksi kas</p></a></li><li class="nav-item"><a href="{{ route('reports.index') }}" class="nav-link @if(request()->routeIs('reports.*')) active @endif"><i class="nav-icon bi bi-file-earmark-bar-graph"></i><p>Laporan</p></a></li><li class="nav-item"><a href="{{ route('settings.fee') }}" class="nav-link @if(request()->routeIs('settings.fee*')) active @endif"><i class="nav-icon bi bi-gear"></i><p>Ubah Tarif</p></a></li></ul></nav></div>
    </aside>
    <main class="app-main"><div class="app-content-header"><div class="container-fluid"><div class="row"><div class="col-sm-7"><h3 class="mb-0">{{ $pageTitle ?? 'Dashboard kas' }}</h3><p class="text-secondary mb-0 mt-1">{{ $pageDescription ?? 'Pantau iuran dan status pembayaran anggota kelompok.' }}</p></div><div class="col-sm-5 text-sm-end mt-3 mt-sm-0">@if(request()->routeIs('dashboard', 'payments.index'))<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="bi bi-plus-lg me-1"></i> Catat pembayaran</button>@endif</div></div></div></div>
    <div class="app-content py-3"><div class="container-fluid">
        @if(session('success'))<div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
        @if($errors->any())<div class="alert alert-danger alert-dismissible" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
        @yield('content')
    </div></div></main>
</div>
@stack('modals')
</body></html>