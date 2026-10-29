<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_login_page_authenticates_users_and_protects_cash_pages(): void
    {
        auth()->logout();
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Selamat datang')->assertSee('Masuk');

        $user = User::factory()->create(['password' => 'secret-password']);
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_monthly_report_displays_member_payment_status(): void
    {
        $member = \App\Models\Member::create(['name' => 'Nadia Putri', 'joined_at' => '2026-10-01']);

        $this->get('/reports?month=2026-10')
            ->assertOk()
            ->assertSee('Laporan pembayaran iuran')
            ->assertSee('Nadia Putri')
            ->assertSee('Belum lunas')
            ->assertSee('Cetak A4');
    }

    public function test_report_shows_up_to_six_months_and_payment_history(): void
    {
        $member = \App\Models\Member::create(['name' => 'Bima', 'joined_at' => '2026-01-01']);
        $payment = Payment::create(['member_id' => $member->id, 'amount' => 50000, 'paid_at' => '2026-10-12', 'note' => 'Setoran Oktober']);
        $payment->months()->create(['member_id' => $member->id, 'month' => '2026-10-01']);

        $this->get('/reports?month=2026-10&months=6')
            ->assertOk()
            ->assertSee('Mei 2026')
            ->assertSee('Oktober 2026')
            ->assertSee('Status iuran per anggota')
            ->assertSee('Riwayat pembayaran yang diterima')
            ->assertSee('Setoran Oktober')
            ->assertSee('50.000');
    }

    public function test_manual_transactions_update_total_cash_balance(): void
    {
        $member = \App\Models\Member::create(['name' => 'Ayu', 'joined_at' => '2026-01-01']);
        Payment::create(['member_id' => $member->id, 'amount' => 40000, 'paid_at' => '2026-10-01']);

        $this->post('/transactions', [
            'type' => 'income', 'description' => 'Donasi', 'amount' => 15000, 'occurred_at' => '2026-10-02',
        ])->assertRedirect();
        $this->post('/transactions', [
            'type' => 'expense', 'description' => 'Konsumsi', 'amount' => 5000, 'occurred_at' => '2026-10-03',
        ])->assertRedirect();

        $this->get('/transactions')
            ->assertOk()
            ->assertSee('Total saldo kas')
            ->assertSee('Rp 50.000')
            ->assertSee('Donasi')
            ->assertSee('Konsumsi');
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_manual_transaction_requires_positive_amount_and_supported_type(): void
    {
        $this->from('/transactions')->post('/transactions', [
            'type' => 'transfer', 'description' => 'Tidak valid', 'amount' => 0, 'occurred_at' => '2026-10-02',
        ])->assertSessionHasErrors(['type', 'amount']);
    }

    public function test_manual_transaction_can_be_updated_and_deleted(): void
    {
        $transaction = \App\Models\Transaction::create([
            'type' => 'income',
            'description' => 'Donasi lama',
            'amount' => 12000,
            'occurred_at' => '2026-10-02',
        ]);

        $this->put("/transactions/{$transaction->id}", [
            'type' => 'expense',
            'description' => 'Pembelian alat',
            'amount' => 7000,
            'occurred_at' => '2026-10-04',
            'note' => 'Perlengkapan',
        ])->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'type' => 'expense',
            'description' => 'Pembelian alat',
            'amount' => 7000,
        ]);
        $this->get('/transactions')->assertOk()->assertSee('Pembelian alat')->assertSee('Perlengkapan');

        $this->delete("/transactions/{$transaction->id}")->assertRedirect();
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_multi_month_payment_marks_each_month_and_prevents_duplicates(): void
    {
        $member = \App\Models\Member::create(['name' => 'Sari', 'joined_at' => '2026-01-01']);

        $this->post('/payments', [
            'member_id' => $member->id,
            'month' => '2026-10',
            'months_count' => 3,
            'paid_at' => '2026-10-10',
        ])->assertRedirect();

        $this->assertDatabaseCount('payment_months', 3);
        $this->assertSame(['2026-10', '2026-11', '2026-12'], $member->paymentMonths()->orderBy('month')->get()->map(fn ($month) => substr($month->month, 0, 7))->all());

        $this->from('/')->post('/payments', [
            'member_id' => $member->id,
            'month' => '2026-11',
            'months_count' => 1,
            'paid_at' => '2026-10-10',
        ])->assertSessionHasErrors('month');
    }

    public function test_fee_can_be_changed_and_requires_a_positive_integer(): void
    {
        $this->get('/settings/fee')->assertOk()->assertSee('Tarif kas bulanan');

        $this->put('/settings/fee', ['monthly_fee' => 75000])->assertRedirect();
        $this->assertSame(75000, Setting::monthlyFee());

        $this->from('/settings/fee')->put('/settings/fee', ['monthly_fee' => 0])->assertSessionHasErrors('monthly_fee');
        $this->from('/settings/fee')->put('/settings/fee', ['monthly_fee' => '12.5'])->assertSessionHasErrors('monthly_fee');
    }

    public function test_payment_amount_uses_active_fee_and_ignores_client_amount(): void
    {
        Setting::setMonthlyFee(23000);
        $member = \App\Models\Member::create(['name' => 'Rina', 'joined_at' => '2026-01-01']);

        $this->post('/payments', [
            'member_id' => $member->id,
            'month' => '2026-10',
            'months_count' => 4,
            'amount' => 1,
            'paid_at' => '2026-10-10',
        ])->assertRedirect();

        $this->assertSame(92000, Payment::firstOrFail()->amount);
        $this->assertDatabaseCount('payment_months', 4);
    }

    public function test_members_module_displays_searchable_member_table(): void
    {
        \App\Models\Member::create(['name' => 'Nadia Putri', 'phone' => '081234567890', 'joined_at' => '2026-10-01']);

        $this->get('/members?q=Nadia')
            ->assertOk()
            ->assertSee('Data anggota')
            ->assertSee('Nadia Putri')
            ->assertSee('081234567890');
    }

    public function test_member_photo_and_nickname_are_saved_and_can_be_updated(): void
    {
        Storage::fake('public');

        $this->post('/members', [
            'name' => 'Nadia Putri',
            'nickname' => 'Nad',
            'phone' => '081234567890',
            'joined_at' => '2026-10-01',
            'photo' => UploadedFile::fake()->image('nadia.jpg'),
        ])->assertRedirect();

        $member = \App\Models\Member::firstOrFail();
        $this->assertSame('Nad', $member->nickname);
        Storage::disk('public')->assertExists($member->photo_path);

        $oldPhoto = $member->photo_path;
        $this->post("/members/{$member->id}", [
            '_method' => 'PUT',
            'name' => 'Nadia Putri',
            'nickname' => 'Nadia',
            'phone' => '081234567890',
            'joined_at' => '2026-10-01',
            'is_active' => '1',
            'photo' => UploadedFile::fake()->image('new-nadia.jpg'),
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame('Nadia', $member->nickname);
        $this->assertNotSame($oldPhoto, $member->photo_path);
        Storage::disk('public')->assertMissing($oldPhoto);
        Storage::disk('public')->assertExists($member->photo_path);
    }

    public function test_member_card_module_renders_landscape_card_and_download_actions(): void
    {
        $member = \App\Models\Member::create(['name' => 'Sari Lestari', 'nickname' => 'Sari', 'joined_at' => '2026-01-01']);

        $this->get('/members/cards')->assertOk()->assertSee('KTA Choplug No Brain')->assertSee('Sari Lestari');
        $this->get("/members/{$member->id}/card")
            ->assertOk()
            ->assertSee('aspect-ratio:1.586/1', false)
            ->assertSee('KTA Choplug No Brain')
            ->assertSee('Logo Choplug Portal')
            ->assertSee('/storage/img/choplug.jpeg')
            ->assertSee('width:72px;height:72px', false)
            ->assertSee('font-size:26px', false)
            ->assertSee('Unduh PNG')
            ->assertSee('Unduh JPG')
            ->assertSee('Sari Lestari');
    }

    public function test_member_can_be_updated_and_invalid_updates_are_rejected(): void
    {
        $member = \App\Models\Member::create(['name' => 'Nama Lama', 'phone' => '0800', 'joined_at' => '2026-10-01']);

        $this->put("/members/{$member->id}", [
            'name' => 'Nama Baru',
            'phone' => '081234',
            'joined_at' => '2026-09-01',
            'is_active' => '0',
        ])->assertRedirect();

        $this->assertDatabaseHas('members', ['id' => $member->id, 'name' => 'Nama Baru', 'phone' => '081234', 'is_active' => 0]);

        $this->from('/members')->put("/members/{$member->id}", [
            'name' => '',
            'phone' => '081234',
            'joined_at' => '2026-09-01',
            'is_active' => '1',
        ])->assertSessionHasErrors('name');
    }

    public function test_deleting_member_also_removes_its_payment_history(): void
    {
        $member = \App\Models\Member::create(['name' => 'Hapus Saya', 'joined_at' => '2026-01-01']);
        $payment = Payment::create(['member_id' => $member->id, 'amount' => 50000, 'paid_at' => '2026-10-10']);
        $payment->months()->create(['member_id' => $member->id, 'month' => '2026-10-01']);

        $this->delete("/members/{$member->id}")->assertRedirect();

        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('payment_months', ['member_id' => $member->id]);
    }

    public function test_payment_history_module_displays_payments_in_a_table(): void
    {
        $member = \App\Models\Member::create(['name' => 'Sari Lestari', 'joined_at' => '2026-01-01']);
        $payment = Payment::create(['member_id' => $member->id, 'amount' => 50000, 'paid_at' => '2026-10-10', 'note' => 'Iuran rutin']);
        $payment->months()->create(['member_id' => $member->id, 'month' => '2026-10-01']);

        $this->get('/payments?q=Sari')
            ->assertOk()
            ->assertSee('Data riwayat pembayaran')
            ->assertSee('Sari Lestari')
            ->assertSee('Iuran rutin')
            ->assertSee('Rp 50.000');
    }
}
