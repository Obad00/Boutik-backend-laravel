<?php

namespace App\Models;

class Product extends ShopScopedModel
{
    protected $fillable = [
        'shop_id',
        'category_id',
        'name',
        'price_sell',
        'price_buy',
        'unit',
        'stock_quantity',
        'stock_alert_threshold',
    ];

    protected $casts = [
        'price_sell' => 'float',
        'price_buy' => 'float',
        'stock_quantity' => 'float',
        'stock_alert_threshold' => 'float',
    ];

    public function idPrefix(): string
    {
        return 'prod';
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
