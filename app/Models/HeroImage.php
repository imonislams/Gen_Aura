<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroImage extends Model
{
    protected $fillable = [
        'path',
        'product_name',
        'product_price',
        'product_old_price',
        'product_url',
        'sort_order',
    ];

    protected $casts = [
        'product_price' => 'integer',
        'product_old_price' => 'integer',
        'sort_order' => 'integer',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
