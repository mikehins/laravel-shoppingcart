<?php

declare(strict_types=1);

use Mikehins\Cart\Cart;
use Mikehins\Cart\Tests\PackageTestCase;

uses(PackageTestCase::class);

it('cart helper returns cart instance', function () {
    $cart = cart();

    expect($cart)->toBeInstanceOf(Cart::class);
});

it('cart helper maintains state', function () {
    cart()->add(123, 'Test Item', 10.00, 1);

    expect(cart()->getContent()->count())->toBe(1);
});
