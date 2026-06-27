<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HallService extends Model
{
    protected $table = 'hall_services';
    protected $fillable = ['hall_id', 'name', 'price'];

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    public function bookings()
    {
        return $this->belongsToMany(HallBooking::class, 'booking_service')->withPivot('price');
    }
}
