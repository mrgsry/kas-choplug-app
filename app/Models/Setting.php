<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function monthlyFee(): int
    {
        $value = static::query()->where('key', 'monthly_fee')->value('value');

        return $value === null ? (int) config('kas.monthly_fee', 50000) : (int) $value;
    }

    public static function setMonthlyFee(int $amount): void
    {
        static::query()->updateOrCreate(['key' => 'monthly_fee'], ['value' => (string) $amount]);
    }
}