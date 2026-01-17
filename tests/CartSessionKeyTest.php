<?php

declare(strict_types=1);

use Mikehins\Cart\Cart;
use Mikehins\Cart\Tests\Helpers\SessionMock;
use Mockery as m;

covers(Cart::class);

it('cart stores items in correct session key', function () {
    $session = new SessionMock;
    $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
    $events->shouldReceive('dispatch');

    $cart = new Cart(
        $session,
        $events,
        'shopping',
        'test_key',
        require (__DIR__.'/Helpers/configMock.php')
    );

    $cart->add(455, 'Sample Item', 100.99, 2, []);

    // The key should be 'test_key_cart_items'
    // internal implementation details:
    // private $sessionKeyCartItems; -> assigned in constructor

    // We can verify by checking the session mock directly
    expect($session->has('test_key_cart_items'))->toBeTrue()
        ->and($session->get('test_key_cart_items'))->not->toBeNull();

    // Also verify it doesn't store in 'test_key' (mutation ConcatRemoveRight)
    expect($session->has('test_key'))->toBeFalse();

    // Verify it doesn't store in '_cart_items' (mutation ConcatRemoveLeft)
    expect($session->has('_cart_items'))->toBeFalse();
});

it('cart stores conditions in correct session key', function () {
    $session = new SessionMock;
    $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
    $events->shouldReceive('dispatch');

    $cart = new Cart(
        $session,
        $events,
        'shopping',
        'test_key',
        require (__DIR__.'/Helpers/configMock.php')
    );

    $condition = new Mikehins\Cart\CartCondition([
        'name' => 'VAT 12.5%',
        'type' => 'tax',
        'target' => 'subtotal',
        'value' => '-5',
    ]);

    $cart->condition($condition);

    expect($session->has('test_key_cart_conditions'))->toBeTrue()
        ->and($session->get('test_key_cart_conditions'))->not->toBeNull();

    expect($session->has('test_key'))->toBeFalse();
    expect($session->has('_cart_conditions'))->toBeFalse();
});
