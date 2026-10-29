@extends('layouts.app', ['pageTitle' => 'Transaksi kas', 'pageDescription' => 'Catat dan pantau pemasukan serta pengeluaran kas di luar iuran anggota.'])

@section('content')
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4"><section class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-secondary">Total saldo kas</div><div class="fs-3 fw-semibold">Rp {{ number_format($balance, 0, ',', '.') }}</div><div class="small text-secondary">Iuran anggota dan transaksi lain</div></div></section></div>
    <div class="col-6 col-md-4"><section class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-secondary">Total pemasukan</div><div class="fs-5 fw-semibold text-success">Rp {{ number_format($income, 0, ',', '.') }}</div></div></section></div>
    <div class="col-6 col-md-4"><section class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-secondary">Total pengeluaran</div><div class="fs-5 fw-semibold text-danger">Rp {{ number_format($expenses, 0, ',', '.') }}</div></div></section></div>
</div>

<section class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"><h5 class="card-title fw-semibold mb-0">Daftar transaksi</h5><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#transactionModal"><i class="bi bi-plus-lg me-1"></i>Tambah transaksi</button></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Jenis</th><th>Deskripsi</th><th>Catatan</th><th class="text-end">Nominal</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @forelse($transactions as $transaction)<tr><td>{{ $transaction->occurred_at->translatedFormat('d M Y') }}</td><td>@if($transaction->type === 'income')<span class="badge text-bg-success">Pemasukan</span>@else<span class="badge text-bg-danger">Pengeluaran</span>@endif</td><td class="fw-semibold">{{ $transaction->description }}</td><td>{{ $transaction->note ?: '-' }}</td><td class="text-end fw-semibold">{{ $transaction->type === 'income' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td><td class="text-end text-nowrap"><button class="btn btn-link btn-sm p-0 me-2" data-bs-toggle="modal" data-bs-target="#editTransaction{{ $transaction->id }}" aria-label="Edit {{ $transaction->description }}"><i class="bi bi-pencil-square"></i></button><form class="d-inline" method="POST" action="{{ route('transactions.destroy', $transaction) }}" onsubmit="return confirm('Hapus transaksi ini? Saldo kas akan diperbarui.')">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0" aria-label="Hapus {{ $transaction->description }}"><i class="bi bi-trash"></i></button></form></td></tr>
        @empty<tr><td colspan="6" class="text-center text-secondary py-4">Belum ada transaksi manual.</td></tr>@endforelse
    </tbody></table></div>
    @if($transactions->hasPages())<div class="card-footer bg-white">{{ $transactions->links() }}</div>@endif
</section>
@endsection

@push('modals')
@foreach($transactions as $transaction)
<div class="modal fade" id="editTransaction{{ $transaction->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('transactions.update', $transaction) }}">@csrf @method('PUT')<div class="modal-header"><h5 class="modal-title">Edit transaksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body">
    <div class="mb-3"><label class="form-label" for="edit_type_{{ $transaction->id }}">Jenis transaksi</label><select class="form-select" id="edit_type_{{ $transaction->id }}" name="type" required><option value="income" @selected($transaction->type === 'income')>Pemasukan</option><option value="expense" @selected($transaction->type === 'expense')>Pengeluaran</option></select></div>
    <div class="mb-3"><label class="form-label" for="edit_description_{{ $transaction->id }}">Deskripsi</label><input class="form-control" id="edit_description_{{ $transaction->id }}" name="description" maxlength="160" value="{{ $transaction->description }}" required></div>
    <div class="mb-3"><label class="form-label" for="edit_amount_{{ $transaction->id }}">Nominal (Rp)</label><input class="form-control" id="edit_amount_{{ $transaction->id }}" type="number" name="amount" min="1" step="1" value="{{ $transaction->amount }}" required></div>
    <div class="mb-3"><label class="form-label" for="edit_occurred_at_{{ $transaction->id }}">Tanggal</label><input class="form-control" id="edit_occurred_at_{{ $transaction->id }}" type="date" name="occurred_at" value="{{ $transaction->occurred_at->format('Y-m-d') }}" required></div>
    <div><label class="form-label" for="edit_note_{{ $transaction->id }}">Catatan <span class="text-secondary">(opsional)</span></label><textarea class="form-control" id="edit_note_{{ $transaction->id }}" name="note" rows="2" maxlength="1000">{{ $transaction->note }}</textarea></div>
</div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan perubahan</button></div></form></div></div>
@endforeach
<div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('transactions.store') }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah transaksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body">
    <div class="mb-3"><label class="form-label" for="type">Jenis transaksi</label><select class="form-select" id="type" name="type" required><option value="income">Pemasukan</option><option value="expense">Pengeluaran</option></select></div>
    <div class="mb-3"><label class="form-label" for="description">Deskripsi</label><input class="form-control" id="description" name="description" maxlength="160" required></div>
    <div class="mb-3"><label class="form-label" for="amount">Nominal (Rp)</label><input class="form-control" id="amount" type="number" name="amount" min="1" step="1" required></div>
    <div class="mb-3"><label class="form-label" for="occurred_at">Tanggal</label><input class="form-control" id="occurred_at" type="date" name="occurred_at" value="{{ now()->format('Y-m-d') }}" required></div>
    <div><label class="form-label" for="transaction_note">Catatan <span class="text-secondary">(opsional)</span></label><textarea class="form-control" id="transaction_note" name="note" rows="2" maxlength="1000"></textarea></div>
    <div class="form-text mt-2">Pembayaran iuran anggota tercatat otomatis dan tidak perlu dimasukkan sebagai transaksi manual.</div>
</div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan transaksi</button></div></form></div></div>
@endpush