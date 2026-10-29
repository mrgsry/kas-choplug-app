<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'habib.gusti@gmail.com'],
            ['name' => 'Habib Gusti', 'password' => 'password123', 'email_verified_at' => now()],
        );

        if (Member::exists()) return;

        foreach (['Alya Putri', 'Bagas Pratama', 'Citra Lestari', 'Dimas Saputra', 'Intan Permata', 'Rafi Nugraha', 'Salsa Amalia', 'Yoga Firmansyah'] as $index => $name) {
            $member = Member::create(['name' => $name, 'phone' => '08'.str_pad((string) (123456789 + $index), 10, '0', STR_PAD_LEFT), 'joined_at' => now()->startOfYear()->toDateString()]);
            $count = $index === 1 ? 0 : ($index === 2 ? 2 : ($index === 4 ? 3 : 1));
            if ($count < 1) continue;

            $payment = Payment::create(['member_id' => $member->id, 'amount' => config('kas.monthly_fee', 50000) * $count, 'paid_at' => now()->toDateString(), 'note' => 'Data contoh']);
            for ($offset = 0; $offset < $count; $offset++) {
                $payment->months()->create(['member_id' => $member->id, 'month' => now()->startOfMonth()->subMonths($offset)->toDateString()]);
            }
        }
    }
}
