<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = ['name', 'nickname', 'phone', 'photo_path', 'joined_at', 'is_active'];

    protected function casts(): array
    {
        return ['joined_at' => 'date', 'is_active' => 'boolean'];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentMonths(): HasMany
    {
        return $this->hasMany(PaymentMonth::class);
    }
}