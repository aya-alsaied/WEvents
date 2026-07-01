<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderProfile extends Model
{
    protected $fillable = [
        'provider_id',
        'theme',
        'pic',
        'about',
        'services_data',
        'public_events',
        'recent',
        'benefits',
    ];

    protected $casts = [
        'theme' => 'array',
        'pic' => 'array',
        'about' => 'array',
        'services_data' => 'array',
        'public_events' => 'array',
        'recent' => 'array',
        'benefits' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}