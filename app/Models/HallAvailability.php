<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HallAvailability extends Model
{
    protected $table = 'hall_availabilities';
    protected $fillable = ['hall_id','date','start_time','end_time', 'availability_type','status',];

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }
        public function bookings()
    {
        return $this->hasMany(HallBooking::class, 'hall_availability_id');
    }
}