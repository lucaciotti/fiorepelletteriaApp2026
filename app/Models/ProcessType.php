<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessType extends Model
{
    protected $guarded = [
        'id'
    ];

    protected $appends = [
        'full_descr',
    ];

    public function getFullDescrAttribute()
    {
        return $this->name . ' - ' . $this->description;
    }


    public function category(): BelongsTo
    {
        return $this->belongsTo(ProcessTypeCategory::class, 'process_type_category_id', 'id');
    }
}
