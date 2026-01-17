<?php

declare(strict_types=1);

use Mikehins\Cart\Cart;
use Mikehins\Cart\Tests\Helpers\SessionMock;
use Mockery as m;

covers(Cart::class);

beforeEach(function () {
    $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
    $events->shouldReceive('dispatch');

    $this->cart1 = new Cart(
        new SessionMock,
        $events,
        'shopping',
        'uniquesessionkey123',
        require (__DIR__.'/Helpers/configMock.php')
    );

    $this->cart2 = new Cart(
        new SessionMock,
        $events,
        'wishlist',
        'uniquesessionkey456',
        require (__DIR__.'/helpers/configMock.php')
    );
});

afterEach(function () {
    m::close();
});

it('cart multiple instances', function () {
    // add 3 items on cart 1
    $itemsForCart1 = [
        [
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 4,
            'attributes' => [],
        ],
        [
            'id' => 568,
            'name' => 'Sample Item 2',
            'price' => 69.25,
            'quantity' => 4,
            'attributes' => [],
        ],
        [
            'id' => 856,
            'name' => 'Sample Item 3',
            'price' => 50.25,
            'quantity' => 4,
            'attributes' => [],
        ],
    ];

    $this->cart1->add($itemsForCart1);

    expect($this->cart1->isEmpty())->toBeFalse()
        ->and($this->cart1->getContent())->toHaveCount(3)
        ->and($this->cart1->getInstanceName())->toBe('shopping');

    // add 1 item on cart 2
    $itemsForCart2 = [
        [
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 4,
            'attributes' => [],
        ],
    ];

    $this->cart2->add($itemsForCart2);

    expect($this->cart2->isEmpty())->toBeFalse()
        ->and($this->cart2->getContent())->toHaveCount(1)
        ->and($this->cart2->getInstanceName())->toBe('wishlist');
});
