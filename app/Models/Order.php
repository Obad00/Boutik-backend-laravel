<?php

namespace App\Models;

class Order extends ShopScopedModel
{
    const UPDATED_AT = null;

    protected $fillable = ['shop_id', 'customer_id', 'total', 'payment_mode'];

    protected $casts = [
        'total' => 'float',
    ];

    public function idPrefix(): string
    {
        return 'ord';
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
