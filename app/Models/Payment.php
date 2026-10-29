<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = ['member_id', 'amount', 'paid_at', 'note'];

    protected function casts(): array
    {
        return ['paid_at' => 'date', 'amount' => 'integer'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function months(): HasMany
    {
        return $this->hasMany(PaymentMonth::class);
    }
}