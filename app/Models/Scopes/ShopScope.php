<?php

namespace App\Models\Scopes;

use App\Support\ShopContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ShopScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound(ShopContext::class)) {
            $builder->where($model->getTable().'.shop_id', app(ShopContext::class)->id());
        }
    }
}
