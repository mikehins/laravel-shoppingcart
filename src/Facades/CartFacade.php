<?php

declare(strict_types=1);

namespace Mikehins\Cart\Facades;

use Illuminate\Support\Facades\Facade;

final class CartFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cart';
    }
}
