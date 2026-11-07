<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'total_orders', 'total_purchase', 'last_order_at'];

    protected $casts = [
        'total_orders' => 'integer',
        'total_purchase' => 'integer',
        'last_order_at' => 'datetime',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return $digits !== '' ? $digits : trim((string) $phone);
    }
}
