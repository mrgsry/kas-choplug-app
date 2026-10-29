@extends('layouts.app', ['pageTitle' => 'Riwayat pembayaran', 'pageDescription' => 'Daftar transaksi pembayaran kas yang telah dicatat.'])

@section('content')
<section class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h5 class="card-title fw-semibold mb-0">Data riwayat pembayaran</h5>
    </div>
    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('payments.index') }}" class="row g-2">
            <div class="col-12 col-md-5"><label class="visually-hidden" for="paymentSearch">Cari anggota</label><input class="form-control form-control-sm" id="paymentSearch" name="q" value="{{ $search }}" placeholder="Cari berdasarkan nama anggota..."></div>
            <div class="col-6 col-md-2 d-grid"><button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Cari</button></div>
            @if($search)<div class="col-6 col-md-2 d-grid"><a class="btn btn-outline-secondary btn-sm" href="{{ route('payments.index') }}">Reset</a></div>@endif
        </form>
    </div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Tanggal bayar</th><th>Anggota</th><th>Periode dibayar</th><th>Catatan</th><th class="text-end">Nominal</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
            @forelse($payments as $payment)
                <tr><td>{{ $payment->paid_at->translatedFormat('d M Y') }}</td><td class="fw-semibold">{{ $payment->member->name }}</td><td>@foreach($payment->months->sortBy('month') as $paidMonth)<span class="month-chip">{{ $paidMonth->month->translatedFormat('M Y') }}</span>@endforeach</td><td>{{ $payment->note ?: '-' }}</td><td class="text-end fw-semibold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td><td class="text-end"><form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('Hapus transaksi ini? Status bulan terkait akan diperbarui.')">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0">Hapus</button></form></td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-secondary py-4">Belum ada transaksi pembayaran.</td></tr>
            @endforelse
        </tbody>
    </table></div>
    @if($payments->hasPages())<div class="card-footer bg-white">{{ $payments->links() }}</div>@endif
</section>
@endsection

@push('modals')
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('payments.store') }}">@csrf<div class="modal-header"><h5 class="modal-title">Catat pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label" for="member_id">Anggota</label><select class="form-select" id="member_id" name="member_id" required><option value="">Pilih anggota</option>@foreach(\App\Models\Member::where('is_active', true)->orderBy('name')->get() as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select></div><div class="row"><div class="col-7 mb-3"><label class="form-label" for="payment_month">Mulai bulan</label><input class="form-control" id="payment_month" type="month" name="month" value="{{ now()->format('Y-m') }}" required></div><div class="col-5 mb-3"><label class="form-label" for="months_count">Bayar berapa bulan</label><select class="form-select" id="months_count" name="months_count" required>@foreach(range(1, 24) as $count)<option value="{{ $count }}" @selected($count === 1)>{{ $count }} bulan</option>@endforeach</select></div></div><div class="mb-3"><label class="form-label" for="paid_at">Tanggal bayar</label><input class="form-control" id="paid_at" type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" required></div><div><label class="form-label" for="note">Catatan <span class="text-secondary">(opsional)</span></label><input class="form-control" id="note" name="note" maxlength="500"></div><div class="form-text mt-2">Total: <strong id="paymentTotal">Rp {{ number_format(\App\Models\Setting::monthlyFee(), 0, ',', '.') }}</strong></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Simpan pembayaran</button></div></form></div></div>
<script>document.addEventListener('DOMContentLoaded',()=>{const count=document.getElementById('months_count');const total=document.getElementById('paymentTotal');const fee={{ \App\Models\Setting::monthlyFee() }};count?.addEventListener('change',()=>{if(total)total.textContent='Rp '+new Intl.NumberFormat('id-ID').format((Number(count.value)||0)*fee)});});</script>
@endpush