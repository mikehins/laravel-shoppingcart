<?php

declare(strict_types=1);

use Mikehins\Cart\Facades\CartFacade;

/**
 * Get the cart instance.
 *
 * @return Mikehins\Cart\Cart
 */
if (! function_exists('cart')) {
    function cart(): Mikehins\Cart\Cart
    {
        return CartFacade::getFacadeRoot();
    }
}
