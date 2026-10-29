<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['type', 'description', 'amount', 'occurred_at', 'note'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'occurred_at' => 'date'];
    }
}