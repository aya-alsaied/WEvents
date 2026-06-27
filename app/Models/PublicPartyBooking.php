<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PublicPartyBooking extends Model
{
    use HasFactory;
    
    protected $table = 'public_party_bookings';
    protected $fillable = ['public_party_id', 'customer_id', 'tickets_count', 'total_price', 'status', 'payment_status', 'payment_deadline'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function publicParty()
    {
        return $this->belongsTo(PublicParty::class);
    }
}