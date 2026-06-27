<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Food extends Model
{
    protected $table = 'food';
    protected $fillable = ['provider_id', 'name', 'description', 'location', 'price', 'image', 'status'];
    protected $casts = [
        'status' => 'boolean',
    ];
    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }
    public function bookings()
    {
        return $this->hasMany(FoodBooking::class);
    }



    public function scopeOfPrice(Builder $query, $amount)
    {
        $amount = floatval($amount);
        //$epsilon = 0.01;
        return $query->where('price', '<=', $amount)->where('status', true)->orderBy('price', 'asc');
    }

    public function scopeOfLocation(Builder $query, $location)
    {
        return $query->where('location', $location)->where('status', true);
    }
}
