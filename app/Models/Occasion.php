<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Occasion extends Model
{
    protected $table = 'occasions';
    protected $fillable = ['name'];
}
