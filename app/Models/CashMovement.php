<?php

namespace App\Models;

class CashMovement extends ShopScopedModel
{
    const UPDATED_AT = null;

    protected $fillable = ['shop_id', 'order_id', 'type', 'amount', 'reason'];

    protected $casts = [
        'amount' => 'float',
    ];

    public function idPrefix(): string
    {
        return 'cshmv';
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
