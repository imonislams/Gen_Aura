<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    const STATUS = ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];

    protected $fillable = [
        'name', 'phone', 'address', 'delivery_area', 'note', 'subtotal', 'ship', 'total',
        'status', 'order_number', 'customer_id', 'payment_method', 'idempotency_key',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'ship' => 'integer',
        'total' => 'integer',
        'customer_id' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function booted(): void
    {
        static::created(function (Order $order) {
            if (empty($order->order_number)) {
                $prefix = Setting::all_()['order_prefix'] ?? 'GA';
                $order->forceFill(['order_number' => sprintf('%s-%06d', $prefix, $order->id)])->saveQuietly();
            }

            $phone = Customer::normalizePhone($order->phone);
            $customer = Customer::firstOrCreate(
                ['phone' => $phone],
                ['name' => $order->name]
            );

            $order->forceFill(['customer_id' => $customer->id])->saveQuietly();

            $customer->update([
                'name' => $order->name,
                'total_orders' => $customer->orders()->where('status', '!=', 'Cancelled')->count(),
                'total_purchase' => $customer->orders()->where('status', '!=', 'Cancelled')->sum('total'),
                'last_order_at' => $order->created_at,
            ]);
        });
    }
}
