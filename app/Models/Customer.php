<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use HasApiTokens;
    protected $fillable = ['name', 'email', 'password', 'phone', 'country', 'type', 'image', 'isApproved'];
    protected $table = 'customers';


    public function hallBookings()
    {
        return $this->hasMany(HallBooking::class);
    }

    public function foodBookings()
    {
        return $this->hasMany(FoodBooking::class);
    }

    public function decorationBookings()
    {
        return $this->hasMany(DecorationBooking::class);
    }

    public function publicPartyBookings()
    {
        return $this->hasMany(PublicPartyBooking::class);
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'walletable');
    }
}
