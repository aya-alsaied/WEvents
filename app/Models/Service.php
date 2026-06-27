<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';
    protected $fillable = ['name'];



    public function providers()
    {
        return $this->belongsToMany(Provider::class, 'provider_service');
    }
}
