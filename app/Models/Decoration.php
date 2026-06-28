<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Decoration extends Model
{
    protected $table = 'decorations';

    protected $fillable = [
        'provider_id',
        'information',
        'location',
        'price',
        'images',
        'status'
    ];

    protected $casts = [
        'images' => 'array',
        'status' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function occasions()
    {
        return $this->belongsToMany(
            Occasion::class,
            'decoration_occasion',
            'decoration_id',
            'occasion_id'
        );
    }

    public function bookings()
    {
        return $this->hasMany(DecorationBooking::class);
    }

    public function scopeOfPrice(Builder $query, $amount)
    {
        return $query
            ->where('price', '<=', $amount)
            ->where('status', true)
            ->orderBy('price');
    }

    public function scopeOfLocation(Builder $query, $location)
    {
        return $query
            ->where('location', $location)
            ->where('status', true);
    }

    public function scopeOfOccasion(Builder $query, $occasionId)
    {
        return $query->whereHas('occasions', function ($q) use ($occasionId) {
            $q->where('occasions.id', $occasionId);
        });
    }
}