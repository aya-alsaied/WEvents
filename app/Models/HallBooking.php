<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HallBooking extends Model
{
    protected $table = 'hall_bookings';

    protected $fillable = [
        'hall_id',
        'hall_availability_id',
        'customer_id',
        'date',
        'start_time',
        'end_time',
        'booking_type',
        'notes',
        'total_price',
        'status',
        'payment_status',
        'payment_deadline',
    ];

    protected $casts = [
        'date' => 'date',
        'payment_deadline' => 'datetime',
        'total_price' => 'decimal:2',
    ];

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function availability()
    {
        return $this->belongsTo(HallAvailability::class, 'hall_availability_id');
    }

    public function hall_services()
    {
        return $this->belongsToMany(HallService::class, 'booking_service')->withPivot('price')->withTimestamps();
    }
}
