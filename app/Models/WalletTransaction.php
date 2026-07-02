<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'bookable_id',
        'bookable_type',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function bookable()
    {
        return $this->morphTo();
    }
}
