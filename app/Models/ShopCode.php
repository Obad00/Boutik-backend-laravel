<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopCode extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    const UPDATED_AT = null;

    protected $fillable = ['code', 'shop_id'];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
