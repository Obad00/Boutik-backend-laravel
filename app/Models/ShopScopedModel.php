<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use App\Models\Scopes\ShopScope;
use App\Support\ShopContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for every model that belongs to a single shop.
 *
 * Applies a global "WHERE shop_id = ?" scope bound to the current request's
 * ShopContext (set by EnsureShopOwner), and auto-fills shop_id on create so
 * controllers never read/accept it from the client.
 */
abstract class ShopScopedModel extends Model
{
    use HasPrefixedId;

    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope);

        static::creating(function (self $model) {
            if (empty($model->shop_id) && app()->bound(ShopContext::class)) {
                $model->shop_id = app(ShopContext::class)->id();
            }
        });
    }
}
