<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodBooking extends Model
{
    protected $table = 'food_bookings';

    protected $fillable = [
        'food_id',
        'customer_id',
        'meal_count',
        'total_price',
        'admin_commission',
        'provider_amount',
        'event_date',
        'event_time',
        'notes',
        'status',
        'payment_status',
        'payment_deadline'
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'admin_commission' => 'decimal:2',
        'provider_amount' => 'decimal:2',
        'event_date' => 'date',
        'payment_deadline' => 'datetime'
    ];

    public function food()
    {
        return $this->belongsTo(Food::class);
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