<?php

declare(strict_types=1);

use Mikehins\Cart\Cart;
use Mikehins\Cart\Facades\CartFacade;

/**
 * Get the cart instance.
 *
 * @return Cart
 */
if (! function_exists('cart')) {
    function cart(): Cart
    {
        return CartFacade::getFacadeRoot();
    }
}
