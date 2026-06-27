<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodBooking extends Model
{
    protected $table = 'food_bookings';
    protected $fillable = [ 'food_id', 'customer_id','meal_count','total_price', 'event_date', 'event_time', 'note', 'status', 'payment_status', 'payment_deadline'  ];

    public function food()
    {
        return $this->belongsTo(Food::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}