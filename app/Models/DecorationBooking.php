<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecorationBooking extends Model
{
    protected $table = 'decoration_bookings';

    protected $fillable = ['decoration_id', 'customer_id', 'event_date', 'event_time', 'notes', 'status', 'payment_status', 'payment_deadline' ];

    public function decoration()
    {
        return $this->belongsTo(Decoration::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}