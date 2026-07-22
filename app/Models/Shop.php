<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasPrefixedId;

    const UPDATED_AT = null;

    protected $fillable = ['name', 'address', 'phone', 'is_active', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function idPrefix(): string
    {
        return 'shop';
    }

    public function code()
    {
        return $this->hasOne(ShopCode::class);
    }

    public function owners()
    {
        return $this->hasMany(ShopOwner::class);
    }

    public function settings()
    {
        return $this->hasOne(Setting::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
