<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMonth extends Model
{
    protected $fillable = ['payment_id', 'member_id', 'month'];

    protected function casts(): array
    {
        return ['month' => 'date'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}