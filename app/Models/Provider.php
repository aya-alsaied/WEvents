<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Provider extends Authenticatable
{
    use HasApiTokens;
    protected $fillable = ['name', 'email', 'password', 'phone', 'country', 'type', 'descriptions', 'image', 'background_image', 'isApproved'];
    protected $table = 'providers';

    protected $casts = [
        'benefits' => 'array',
    ];


    public function profile()
    {
        return $this->hasOne(ProviderProfile::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'provider_service');
    }


    public function halls()
    {
        return $this->hasMany(Hall::class, 'provider_id');
    }

    public function foods()
    {
        return $this->hasMany(Food::class, 'provider_id');
    }

    public function decorations()
    {
        return $this->hasMany(Decoration::class, 'provider_id');
    }

    public function publicParties()
    {
        return $this->hasMany(PublicParty::class, 'provider_id');
    }





    public function scopeUnapproved(Builder $query)
    {
        return $query->where('isApproved', false);
    }

    // Scope للي approved
    public function scopeApproved(Builder $query)
    {
        return $query->where('isApproved', true);
    }
}
