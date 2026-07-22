<?php

namespace App\Models;

class Setting extends ShopScopedModel
{
    public $timestamps = false;

    protected $fillable = ['shop_id', 'shop_name', 'address', 'phone', 'receipt_footer', 'admin_pin_hash'];

    protected $hidden = ['admin_pin_hash'];

    public function idPrefix(): string
    {
        return 'set';
    }
}
