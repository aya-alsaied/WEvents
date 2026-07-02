<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PublicPartyBooking extends Model
{
    use HasFactory;

    protected $table = 'public_party_bookings';

    protected $fillable = [
        'public_party_id',
        'customer_id',
        'tickets_count',
        'total_price',
        'admin_commission',
        'provider_amount',
        'status',
        'payment_status',
        'payment_deadline'
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'admin_commission' => 'decimal:2',
        'provider_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function publicParty()
    {
        return $this->belongsTo(PublicParty::class);
    }

    public function transactions()
    {
        return $this->morphMany(
            WalletTransaction::class,
            'bookable'
        );
    }
}