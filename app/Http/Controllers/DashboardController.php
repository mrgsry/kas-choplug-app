<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentMonth;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function report(Request $request)
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'months' => ['nullable', 'integer', 'between:1,6'],
        ]);
        $month = Carbon::parse($request->query('month', now()->format('Y-m')).'-01')->startOfMonth();
        $monthCount = (int) $request->query('months', 1);
        $start = $month->copy()->subMonths($monthCount - 1)->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $monthlyFee = Setting::monthlyFee();
        $members = Member::query()->where('is_active', true)
            ->with(['paymentMonths' => fn ($query) => $query->whereBetween('month', [$start->toDateString(), $end->toDateString()])->orderBy('month')])
            ->orderBy('name')->get();
        $payments = Payment::query()->with(['member', 'months'])
            ->whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('paid_at')->get();
        $periods = collect(range(0, $monthCount - 1))->map(fn ($offset) => $start->copy()->addMonths($offset));
        $rows = $members->map(function (Member $member) use ($periods, $monthlyFee) {
            $paidMonths = $member->paymentMonths->keyBy(fn ($paid) => $paid->month->format('Y-m'));
            $monthly = $periods->map(fn ($period) => [
                'month' => $period,
                'paid' => $paidMonths->has($period->format('Y-m')),
                'amount' => $paidMonths->has($period->format('Y-m')) ? $monthlyFee : 0,
            ]);
            $through = null;
            foreach ($member->paymentMonths as $paid) {
                $through = $paid->month->copy()->startOfMonth();
            }

            return ['member' => $member, 'monthly' => $monthly, 'through' => $through];
        });
        $monthlyTotals = $periods->map(fn ($period) => [
            'month' => $period,
            'collected' => $payments->sum(fn ($payment) => $payment->months->where('month', $period->toDateString())->isNotEmpty() ? $monthlyFee : 0),
            'payments' => $members->sum(fn ($member) => $member->paymentMonths->where('month', $period->toDateString())->count()),
        ]);

        return view('reports.index', [
            'month' => $month,
            'months' => $monthCount,
            'start' => $start,
            'periods' => $periods,
            'payments' => $payments,
            'monthlyTotals' => $monthlyTotals,
            'rows' => $rows,
            'totalMembers' => $members->count(),
            'paidCount' => $rows->sum(fn ($row) => $row['monthly']->where('paid', true)->count()),
            'unpaidCount' => $rows->sum(fn ($row) => $row['monthly']->where('paid', false)->count()),
            'collected' => $payments->sum('amount'),
            'monthlyFee' => $monthlyFee,
        ]);
    }

    public function transactions()
    {
        $transactions = Transaction::query()->orderByDesc('occurred_at')->orderByDesc('id')->paginate(20);
        $income = Payment::sum('amount') + Transaction::where('type', 'income')->sum('amount');
        $expenses = Transaction::where('type', 'expense')->sum('amount');

        return view('transactions.index', compact('transactions', 'income', 'expenses') + ['balance' => $income - $expenses]);
    }

    public function storeTransaction(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'description' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'occurred_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        Transaction::create($data);

        return back()->with('success', 'Transaksi berhasil dicatat.');
    }

    public function updateTransaction(Request $request, Transaction $transaction)
    {
        $transaction->update($request->validate([
            'type' => ['required', 'in:income,expense'],
            'description' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'integer', 'min:1'],
            'occurred_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]));

        return back()->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroyTransaction(Transaction $transaction)
    {
        $transaction->delete();

        return back()->with('success', 'Transaksi berhasil dihapus.');
    }

    public function index(Request $request)
    {
        $month = Carbon::createFromFormat('Y-m', $request->query('month', now()->format('Y-m')))->startOfMonth();
        $monthlyFee = Setting::monthlyFee();
        $members = Member::query()->where('is_active', true)->with('paymentMonths')->orderBy('name')->get();
        $monthKey = $month->toDateString();

        $rows = $members->map(function (Member $member) use ($monthKey, $monthlyFee) {
            $months = $member->paymentMonths->sortBy('month');
            $paidThisMonth = $months->contains(fn ($paid) => $paid->month->toDateString() === $monthKey);
            $through = null;
            foreach ($months as $paid) {
                if ($through === null) {
                    if ($paid->month->toDateString() !== $member->joined_at->copy()->startOfMonth()->toDateString()) {
                        break;
                    }
                    $through = $paid->month->copy()->startOfMonth();
                    continue;
                }
                if ($paid->month->toDateString() !== $through->copy()->addMonth()->toDateString()) {
                    break;
                }
                $through = $paid->month->copy()->startOfMonth();
            }

            return [
                'member' => $member,
                'paid' => $paidThisMonth,
                'months_count' => $months->count(),
                'through' => $through,
                'amount' => $months->where('month', $monthKey)->count() * $monthlyFee,
            ];
        });

        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status', 'all');
        $visibleRows = $rows->filter(fn ($row) =>
            (!$search || str_contains(mb_strtolower($row['member']->name), mb_strtolower($search))) &&
            ($status === 'all' || ($status === 'paid' && $row['paid']) || ($status === 'unpaid' && !$row['paid']))
        )->values();

        return view('dashboard', [
            'month' => $month,
            'monthlyFee' => $monthlyFee,
            'rows' => $visibleRows,
            'totalMembers' => $members->count(),
            'paidCount' => $rows->where('paid', true)->count(),
            'unpaidCount' => $rows->where('paid', false)->count(),
            'collected' => $rows->sum('amount'),
            'recentPayments' => Payment::with('member', 'months')->latest('paid_at')->latest()->limit(6)->get(),
            'months' => collect(range(0, 11))->map(fn ($offset) => $month->copy()->subMonths($offset)->startOfMonth()),
            'balance' => Payment::sum('amount') + Transaction::where('type', 'income')->sum('amount') - Transaction::where('type', 'expense')->sum('amount'),
        ]);
    }

    public function members(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $members = Member::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->withCount('payments')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('members.index', compact('members', 'search'));
    }


    public function memberPhoto(string $filename)
    {
        abort_unless($filename === basename($filename), 404);

        $path = 'members/'.$filename;
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
    public function memberCards(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $members = Member::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('members.cards', compact('members', 'search'));
    }

    public function memberCard(Member $member)
    {
        return view('members.card', compact('member'));
    }

    public function payments(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $payments = Payment::query()
            ->with(['member', 'months'])
            ->when($search !== '', fn ($query) => $query->whereHas('member', fn ($memberQuery) => $memberQuery->where('name', 'like', "%{$search}%")))
            ->latest('paid_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('payments.index', compact('payments', 'search'));
    }

    public function storePayment(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'month' => ['required', 'date_format:Y-m'],
            'months_count' => ['required', 'integer', 'min:1', 'max:24'],
            'paid_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $start = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $dueMonths = collect(range(0, (int) $data['months_count'] - 1))->map(fn ($offset) => $start->copy()->addMonths($offset));

        try {
            DB::transaction(function () use ($data, $dueMonths) {
                $duplicates = PaymentMonth::where('member_id', $data['member_id'])->whereIn('month', $dueMonths->map->toDateString())->exists();
                if ($duplicates) {
                    throw ValidationException::withMessages(['month' => 'Ada bulan dalam rentang ini yang sudah tercatat lunas.']);
                }

                $payment = Payment::create([
                    'member_id' => $data['member_id'],
                    'amount' => Setting::monthlyFee() * count($dueMonths),
                    'paid_at' => $data['paid_at'],
                    'note' => $data['note'] ?? null,
                ]);

                foreach ($dueMonths as $dueMonth) {
                    $payment->months()->create(['member_id' => $data['member_id'], 'month' => $dueMonth->toDateString()]);
                }
            });
        } catch (QueryException $exception) {
            if (! str_contains($exception->getMessage(), 'payment_months.member_id, payment_months.month')) throw $exception;
            throw ValidationException::withMessages(['month' => 'Ada bulan dalam rentang ini yang sudah tercatat lunas.']);
        }

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function storeMember(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'nickname' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'joined_at' => ['required', 'date'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('members', 'public');
        }
        Member::create($data);

        return back()->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function updateMember(Request $request, Member $member)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'nickname' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'joined_at' => ['required', 'date'],
            'is_active' => ['required', 'boolean'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);
        if ($request->hasFile('photo')) {
            if ($member->photo_path) {
                Storage::disk('public')->delete($member->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('members', 'public');
        }
        $member->update($data);

        return back()->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroyMember(Member $member)
    {
        if ($member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
        }
        $member->delete();

        return back()->with('success', 'Anggota dan seluruh riwayat pembayarannya berhasil dihapus.');
    }

    public function destroyPayment(Payment $payment)
    {
        $payment->delete();

        return back()->with('success', 'Transaksi berhasil dihapus dan status iuran diperbarui.');
    }
}