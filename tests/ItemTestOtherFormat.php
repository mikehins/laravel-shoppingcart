<?php

declare(strict_types=1);

use Mikehins\Cart\Cart;
use Mikehins\Cart\ItemCollection;
use Mikehins\Cart\Tests\helpers\SessionMock;
use Mockery as m;

covers(ItemCollection::class);

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

it('item get sum price using property', function () {
    $this->cart->add(455, 'Sample Item', 100.99, 2, []);

    $item = $this->cart->get(455);

    expect($item->getPriceSum())->toBe('201,980');
});

it('item get sum price using array style', function () {
    $this->cart->add(455, 'Sample Item', 100.99, 2, []);

    $item = $this->cart->get(455);

    expect($item->getPriceSum())->toBe('201,980');
});
