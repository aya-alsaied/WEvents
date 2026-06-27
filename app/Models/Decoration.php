<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


class Decoration extends Model
{
    protected $table = 'decorations';
    protected $fillable = ['provider_id', 'information', 'location', 'price', 'images', 'status'];
    protected $casts = [
        'images' => 'array',
        'status' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function bookings() 
    {
        return $this->hasMany(DecorationBooking::class);    
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
