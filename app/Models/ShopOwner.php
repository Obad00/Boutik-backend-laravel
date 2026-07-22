<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class ShopOwner extends Authenticatable
{
    use HasApiTokens, HasPrefixedId;

    const UPDATED_AT = null;

    protected $fillable = ['shop_id', 'name', 'email', 'password_hash', 'role'];

    protected $hidden = ['password_hash'];

    public function idPrefix(): string
    {
        return 'owner';
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
