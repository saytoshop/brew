<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrewComment extends Model
{
    protected $fillable = ['brew_id', 'content'];

    public function brew(): BelongsTo
    {
        return $this->belongsTo(Brew::class);
    }
}
