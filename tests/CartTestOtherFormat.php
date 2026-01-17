<?php

declare(strict_types=1);

use Mikehins\Cart\Cart;
use Mikehins\Cart\Tests\Helpers\SessionMock;
use Mockery as m;

covers(Cart::class);

beforeEach(function () {
    $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
    $events->shouldReceive('dispatch');

    $this->cart = new Cart(
        new SessionMock,
        $events,
        'shopping',
        'SAMPLESESSIONKEY',
        require (__DIR__.'/Helpers/configMockOtherFormat.php')
    );
});

afterEach(function () {
    m::close();
});

it('cart sub total', function () {
    $items = [
        [
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 1,
            'attributes' => [],
        ],
        [
            'id' => 568,
            'name' => 'Sample Item 2',
            'price' => 69.25,
            'quantity' => 1,
            'attributes' => [],
        ],
        [
            'id' => 856,
            'name' => 'Sample Item 3',
            'price' => 50.25,
            'quantity' => 1,
            'attributes' => [],
        ],
    ];

    $this->cart->add($items);

    expect($this->cart->getSubTotal())->toBe('187,490');

    $this->cart->remove(456);

    expect($this->cart->getSubTotal())->toBe('119,500');
});

it('sub total when item quantity is updated', function () {
    $items = [
        [
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 3,
            'attributes' => [],
        ],
        [
            'id' => 568,
            'name' => 'Sample Item 2',
            'price' => 69.25,
            'quantity' => 1,
            'attributes' => [],
        ],
    ];

    $this->cart->add($items);

    expect($this->cart->getSubTotal())->toBe('273,220');

    $this->cart->update(456, ['quantity' => 2]);

    expect($this->cart->getSubTotal())->toBe('409,200');
});

it('sub total when item quantity is updated by reduced', function () {
    $items = [
        [
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 3,
            'attributes' => [],
        ],
        [
            'id' => 568,
            'name' => 'Sample Item 2',
            'price' => 69.25,
            'quantity' => 1,
            'attributes' => [],
        ],
    ];

    $this->cart->add($items);

    expect($this->cart->getSubTotal())->toBe('273,220');

    $this->cart->update(456, ['quantity' => -1]);

    $item = $this->cart->get(456);

    expect($item['quantity'])->toBe(2)
        ->and($this->cart->getSubTotal())->toBe('205,230');
});
