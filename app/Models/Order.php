<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    const STATUS = ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];
    protected $fillable = ['name', 'phone', 'address', 'subtotal', 'ship', 'total', 'status'];
    public function items() { return $this->hasMany(OrderItem::class); }
}
