<?php

namespace App\Models\Concerns;

use App\Support\Ids;
use Illuminate\Database\Eloquent\Model;

trait HasPrefixedId
{
    public static function bootHasPrefixedId(): void
    {
        static::creating(function (Model $model) {
            $key = $model->getKeyName();
            if (empty($model->{$key})) {
                $model->{$key} = Ids::generate($model->idPrefix());
            }
        });
    }

    public function initializeHasPrefixedId(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }

    abstract public function idPrefix(): string;
}
