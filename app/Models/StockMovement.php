<?php

namespace App\Models;

class StockMovement extends ShopScopedModel
{
    const UPDATED_AT = null;

    protected $fillable = ['shop_id', 'product_id', 'type', 'quantity', 'reason'];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function idPrefix(): string
    {
        return 'stkmv';
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
