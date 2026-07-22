<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Database\Eloquent\Model;

/**
 * No shop_id column (matches schema.sql) — isolation is inherited
 * transitively through the parent Order, which is always shop-scoped.
 * Do not add a shop_id scope/column here.
 */
class OrderItem extends Model
{
    use HasPrefixedId;

    public $timestamps = false;

    protected $fillable = ['order_id', 'product_id', 'quantity', 'unit_price'];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
    ];

    public function idPrefix(): string
    {
        return 'oit';
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
