<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Occasion extends Model
{
    protected $fillable = ['name'];

    public function decorations()
    {
        return $this->belongsToMany(
            Decoration::class,
            'decoration_occasion',
            'occasion_id',
            'decoration_id'
        );
    }
}