@extends('layouts.app', ['pageTitle' => 'Ubah tarif', 'pageDescription' => 'Atur iuran kas bulanan untuk pembayaran berikutnya.'])

@section('content')
<section class="card border-0 shadow-sm" style="max-width: 640px">
    <div class="card-header bg-white py-3"><h5 class="card-title fw-semibold mb-0">Tarif kas bulanan</h5></div>
    <form method="POST" action="{{ route('settings.fee.update') }}">
        @csrf
        @method('PUT')
        <div class="card-body">
            <label class="form-label fw-semibold" for="monthly_fee">Tarif per anggota</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input class="form-control" id="monthly_fee" name="monthly_fee" type="number" min="1" step="1" value="{{ old('monthly_fee', $monthlyFee) }}" required>
            </div>
            <div class="form-text">Tarif baru berlaku untuk pembayaran yang dicatat setelah perubahan.</div>
        </div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Simpan tarif</button></div>
    </form>
</section>
@endsection