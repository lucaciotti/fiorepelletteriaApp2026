<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    protected $guarded = [
        'id'
    ];

    public function productProcessTypes(): HasMany
    {
        return $this->hasMany(ProductProcessType::class);
    }

    public function processTypes(): HasManyThrough
    {
        return $this->hasManyThrough(ProcessType::class, ProductProcessType::class);
    }
}
