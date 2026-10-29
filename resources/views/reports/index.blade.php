@extends('layouts.app', ['pageTitle' => 'Laporan pembayaran', 'pageDescription' => 'Rekap status iuran anggota dan histori pembayaran hingga enam bulan.'])

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3 no-print">
    <form method="GET" action="{{ route('reports.index') }}" class="d-flex flex-wrap align-items-end gap-2">
        <div><label class="form-label small fw-semibold mb-1" for="month">Bulan akhir</label><input class="form-control" id="month" type="month" name="month" value="{{ $month->format('Y-m') }}"></div>
        <div><label class="form-label small fw-semibold mb-1" for="months">Rentang</label><select class="form-select" id="months" name="months">@foreach(range(1, 6) as $count)<option value="{{ $count }}" @selected($months === $count)>{{ $count }} {{ $count === 1 ? 'bulan' : 'bulan' }}</option>@endforeach</select></div>
        <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
    </form>
    <button class="btn btn-outline-primary" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak A4</button>
</div>

<article class="report-sheet p-4 p-md-5 shadow-sm">
    <header class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
        <div class="d-flex align-items-center gap-3"><img src="{{ asset('storage/img/choplug.jpeg') }}" alt="Logo Choplug" class="report-header-logo" style="width:64px;height:64px;object-fit:cover"><div><div class="small text-uppercase fw-semibold print-navy">Choplug Portal</div><h1 class="h3 fw-bold mb-1 print-navy">Laporan pembayaran iuran</h1><div class="text-secondary">{{ $start->translatedFormat('F Y') }} - {{ $month->translatedFormat('F Y') }}</div></div></div>
        <div class="text-end small text-secondary"><i class="bi bi-calendar3 me-1"></i>Dicetak {{ now()->translatedFormat('d F Y') }}</div>
    </header>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="border rounded p-3 h-100"><div class="small text-secondary">Anggota aktif</div><div class="fs-4 fw-bold print-navy">{{ $totalMembers }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="border rounded p-3 h-100"><div class="small text-secondary">Bulan lunas</div><div class="fs-4 fw-bold text-success">{{ $paidCount }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="border rounded p-3 h-100"><div class="small text-secondary">Bulan belum lunas</div><div class="fs-4 fw-bold text-danger">{{ $unpaidCount }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="border rounded p-3 h-100"><div class="small text-secondary">Kas diterima pada rentang</div><div class="fs-6 fw-bold print-navy">Rp {{ number_format($collected, 0, ',', '.') }}</div></div></div>
    </div>

    <h2 class="h6 fw-bold mb-2">Ringkasan per bulan</h2>
    <div class="table-responsive mb-4"><table class="table table-bordered table-sm align-middle mb-0"><thead><tr><th>Bulan iuran</th><th class="text-center">Anggota lunas</th><th class="text-end">Penerimaan iuran</th></tr></thead><tbody>
        @foreach($monthlyTotals as $total)<tr><td>{{ $total['month']->translatedFormat('F Y') }}</td><td class="text-center">{{ $total['payments'] }} / {{ $totalMembers }}</td><td class="text-end">Rp {{ number_format($total['collected'], 0, ',', '.') }}</td></tr>@endforeach
    </tbody></table></div>

    <h2 class="h6 fw-bold mb-2">Status iuran per anggota</h2>
    <div class="table-responsive mb-4"><table class="table table-bordered align-middle report-table mb-0"><thead><tr><th>Nama anggota</th>@foreach($periods as $period)<th class="text-center">{{ $period->translatedFormat('M y') }}</th>@endforeach<th class="text-end">Total status lunas</th></tr></thead><tbody>
        @forelse($rows as $row)<tr><td class="fw-semibold">{{ $row['member']->name }}<div class="small text-secondary fw-normal">{{ $row['member']->phone ?: 'Anggota sejak '.$row['member']->joined_at->translatedFormat('M Y') }}</div></td>@foreach($row['monthly'] as $item)<td class="text-center">@if($item['paid'])<span class="badge text-bg-success">Lunas</span>@else<span class="badge text-bg-danger">Belum lunas</span>@endif</td>@endforeach<td class="text-end">Rp {{ number_format($row['monthly']->sum('amount'), 0, ',', '.') }}</td></tr>
        @empty<tr><td colspan="{{ $months + 2 }}" class="text-center text-secondary py-4">Belum ada anggota aktif.</td></tr>@endforelse
    </tbody></table></div>

    <h2 class="h6 fw-bold mb-2">Riwayat pembayaran yang diterima</h2>
    <div class="table-responsive"><table class="table table-bordered table-sm align-middle mb-0"><thead><tr><th>Tanggal bayar</th><th>Anggota</th><th>Bulan iuran</th><th>Catatan</th><th class="text-end">Nominal</th></tr></thead><tbody>
        @forelse($payments as $payment)<tr><td>{{ $payment->paid_at->translatedFormat('d M Y') }}</td><td>{{ $payment->member->name }}</td><td>{{ $payment->months->sortBy('month')->map(fn ($paid) => $paid->month->translatedFormat('M Y'))->implode(', ') }}</td><td>{{ $payment->note ?: '-' }}</td><td class="text-end">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td></tr>
        @empty<tr><td colspan="5" class="text-center text-secondary py-3">Belum ada pembayaran pada rentang ini.</td></tr>@endforelse
    </tbody><tfoot><tr class="fw-bold"><td colspan="4" class="text-end">Total penerimaan aktual</td><td class="text-end">Rp {{ number_format($collected, 0, ',', '.') }}</td></tr></tfoot></table></div>

</article>
@endsection