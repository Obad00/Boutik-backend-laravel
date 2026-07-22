<?php

namespace App\Models;

class CreditPayment extends ShopScopedModel
{
    const UPDATED_AT = null;

    protected $fillable = ['shop_id', 'customer_id', 'amount'];

    protected $casts = [
        'amount' => 'float',
    ];

    public function idPrefix(): string
    {
        return 'crpay';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
