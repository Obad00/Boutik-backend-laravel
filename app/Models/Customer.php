<?php

namespace App\Models;

class Customer extends ShopScopedModel
{
    const UPDATED_AT = null;

    protected $fillable = ['shop_id', 'name', 'phone', 'current_debt'];

    protected $casts = [
        'current_debt' => 'float',
    ];

    public function idPrefix(): string
    {
        return 'cust';
    }
}
