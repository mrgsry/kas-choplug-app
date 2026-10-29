<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('settings.fee', ['monthlyFee' => Setting::monthlyFee()]);
    }

    public function updateFee(Request $request)
    {
        $data = $request->validate([
            'monthly_fee' => ['required', 'integer', 'min:1'],
        ]);

        Setting::setMonthlyFee((int) $data['monthly_fee']);

        return back()->with('success', 'Tarif kas berhasil diperbarui.');
    }
}