<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $fillable = ['name', 'price', 'purchase_date'];

    protected $casts = [
        'price' => 'decimal:2',
        'purchase_date' => 'date',
    ];
}
