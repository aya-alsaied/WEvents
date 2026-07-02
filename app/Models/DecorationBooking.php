<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecorationBooking extends Model
{
    protected $table = 'decoration_bookings';

    protected $fillable = [
        'decoration_id',
        'customer_id',
        'event_date',
        'event_time',
        'notes',
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

    public function decoration()
    {
        return $this->belongsTo(Decoration::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions()
    {
        return $this->morphMany(
            WalletTransaction::class,
            'bookable'
        );
    }
}