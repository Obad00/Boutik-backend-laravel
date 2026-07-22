<?php

namespace App\Models;

class Category extends ShopScopedModel
{
    public $timestamps = false;

    protected $fillable = ['shop_id', 'name'];

    public function idPrefix(): string
    {
        return 'cat';
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
