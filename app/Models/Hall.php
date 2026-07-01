<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


class Hall extends Model
{
    protected $table = 'halls';
    protected $fillable = ['provider_id', 'name', 'type', 'CapacityOfPeople', 'location', 'full_day_price', 'hour_price', 'information', 'rules', 'images', 'buffer_minutes', 'status'];
    protected $casts = [
        'images' => 'array',
        'status' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function availabilities()
    {
        return $this->hasMany(HallAvailability::class);
    }

    public function bookings()
    {
        return $this->hasMany(HallBooking::class);
        
    }

    public function services()
    {
        return $this->hasMany(HallService::class);
    }



    public function scopeInside(Builder $query)
    {
        return $query->where('type', 'inside')->where('status', true);
    }

    // Scope للي type
    public function scopeOutside(Builder $query)
    {
        return $query->where('type', 'outside')->where('status', true);
    }

    public function scopeOfPrice(
        Builder $query,
        $amount,
        $priceType = 'full_day'
    ) {
        $column = $priceType === 'hourly'
            ? 'hour_price'
            : 'full_day_price';

        return $query
            ->where($column, '<=', $amount)
            ->where('status', true)
            ->orderBy($column);
    }

    public function scopeMinCapacity(Builder $query, $value)
    {
        return $query->where('CapacityOfPeople', '>=', $value)->where('status', true)->orderBy('CapacityOfPeople', 'asc');
    }

    public function scopeOfLocation(Builder $query, $location)
    {
        return $query->where('location', $location)->where('status', true);
    }
}
