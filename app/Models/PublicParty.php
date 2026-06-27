<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PublicParty extends Model
{
    protected $table = 'public_parties';
    protected $fillable = ['provider_id', 'name', 'information', 'date', 'start_time', 'end_time', 'location', 'price', 'image', 'tickets', 'status'];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function bookings()
    {
        return $this->hasMany(PublicPartyBooking::class);
    }

    public function scopeOfLocation(Builder $query, $location)
    {
        return $query->where('location', $location)->where('status', true);
    }

    public function scopeOfPrice(Builder $query, $amount)
    {
        $amount = floatval($amount);
        //$epsilon = 0.01;
        return $query->where('price', '<=', $amount)->where('status', true)->orderBy('price', 'asc');
    }
}