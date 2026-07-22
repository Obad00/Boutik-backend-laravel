<?php

namespace App\Support;

final class ShopContext
{
    public function __construct(private readonly string $shopId)
    {
    }

    public function id(): string
    {
        return $this->shopId;
    }
}
