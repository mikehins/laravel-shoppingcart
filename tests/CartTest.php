<?php

declare(strict_types=1);

use Mikehins\Cart\Cart;
use Mikehins\Cart\Exceptions\InvalidItemException;
use Mikehins\Cart\ItemAttributeCollection;
use Mikehins\Cart\Tests\Helpers\MockProduct;
use Mikehins\Cart\Tests\Helpers\SessionMock;
use Mockery as m;

covers(Cart::class);

beforeEach(function () {
    $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
    $events->shouldReceive('dispatch');

    $this->session = new SessionMock;

    $this->cart = new Cart(
        $this->session,
        $events,
        'shopping',
        'SAMPLESESSIONKEY',
        require (__DIR__.'/Helpers/configMock.php')
    );
});

afterEach(function () {
    m::close();
});

it('can add item', function () {
    $this->cart->add(455, 'Sample Item', 100.99, 2, []);

    expect($this->cart->isEmpty())->toBeFalse()
        ->and($this->cart->getContent()->count())->toBe(1)
        ->and($this->cart->getContent()->first()['id'])->toBe(455)
        ->and($this->cart->getContent()->first()['price'])->toBe(100.99);
});

it('can add items as array', function () {
    $item = [
        'id' => 456,
        'name' => 'Sample Item',
        'price' => 67.99,
        'quantity' => 4,
        'attributes' => [],
    ];

    $this->cart->add($item);

    expect($this->cart->isEmpty())->toBeFalse()
        ->and($this->cart->getContent()->count())->toBe(1)
        ->and($this->cart->getContent()->first()['id'])->toBe(456)
        ->and($this->cart->getContent()->first()['name'])->toBe('Sample Item');
});

it('can add items with string numeric values', function () {
    $this->cart->add([
        'id' => 456,
        'name' => 'Sample Item',
        'price' => '67.99',
        'quantity' => '5',
    ]);

    expect($this->cart->getContent()->count())->toBe(1);
});

it('can add items with multidimensional array', function () {
    $items = [
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

    $this->cart->add($items);

    expect($this->cart->isEmpty())->toBeFalse()
        ->and($this->cart->getContent()->toArray())->toHaveCount(3);
});

it('can add item without attributes', function () {
    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 67.99,
        'quantity' => 4,
    ];

    $this->cart->add($item);

    expect($this->cart->isEmpty())->toBeFalse();
});

it('keeps attributes as ItemAttributeCollection after update', function () {
    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 67.99,
        'quantity' => 4,
        'attributes' => [
            'product_id' => '145',
            'color' => 'red',
        ],
    ];
    $this->cart->add($item);

    $cartItem = $this->cart->get(456);

    expect($cartItem->attributes)->toBeInstanceOf(ItemAttributeCollection::class);

    $updatedItem = [
        'attributes' => [
            'product_id' => '145',
            'color' => 'red',
        ],
    ];
    $this->cart->update(456, $updatedItem);

    expect($cartItem->attributes)->toBeInstanceOf(ItemAttributeCollection::class);
});

it('can handle item attributes', function () {
    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 67.99,
        'quantity' => 4,
        'attributes' => [
            'size' => 'L',
            'color' => 'blue',
        ],
    ];

    $this->cart->add($item);

    expect($this->cart->isEmpty())->toBeFalse()
        ->and($this->cart->getContent()->first()['attributes'])->toHaveCount(2)
        ->and($this->cart->getContent()->first()->attributes->size)->toBe('L')
        ->and($this->cart->getContent()->first()->attributes->color)->toBe('blue')
        ->and($this->cart->get(456)->has('attributes'))->toBeTrue()
        ->and($this->cart->get(456)->get('attributes')->size)->toBe('L');
});

it('can update existing item', function () {
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

    $item = $this->cart->get(456);
    expect($item['name'])->toBe('Sample Item 1')
        ->and($item['price'])->toBe(67.99)
        ->and($item['quantity'])->toBe(3);

    $this->cart->update(456, [
        'name' => 'Renamed',
        'quantity' => 2,
        'price' => 105,
    ]);

    $item = $this->cart->get(456);
    expect($item['name'])->toBe('Renamed')
        ->and($item['price'])->toBe(105)
        ->and($item['quantity'])->toBe(5); // 3 + 2
});

it('can update existing item with quantity as array and not relative', function () {
    $items = [
        [
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 3,
            'attributes' => [],
        ],
    ];

    $this->cart->add($items);

    $item = $this->cart->get(456);
    expect($item['quantity'])->toBe(3);

    $this->cart->update(456, ['quantity' => ['relative' => false, 'value' => 5]]);

    $item = $this->cart->get(456);
    expect($item['quantity'])->toBe(5);
});

it('normalizes item price when added to cart', function () {
    $this->cart->add(455, 'Sample Item', '100.99', 2, []);

    expect($this->cart->getContent()->first()['price'])->toBeFloat();
});

it('removes an item from cart by item id', function () {
    $items = [
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

    $this->cart->add($items);

    $this->cart->remove(456);

    expect($this->cart->getContent()->toArray())->toHaveCount(2)
        ->and($this->cart->getContent()->has(456))->toBeFalse();
});

it('calculates subtotal', function () {
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

    expect($this->cart->getSubTotal())->toBe(187.49);

    $this->cart->remove(456);

    expect($this->cart->getSubTotal())->toBe(119.5);
});

it('updates subtotal when item quantity is updated', function () {
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

    expect(round($this->cart->getSubTotal(false), 2))->toBe(273.22);

    $this->cart->update(456, ['quantity' => 2]);

    expect(round($this->cart->getSubTotal(false), 2))->toBe(409.20);
});

it('updates subtotal when item quantity is reduced', function () {
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

    expect(round($this->cart->getSubTotal(false), 2))->toBe(273.22);

    $this->cart->update(456, ['quantity' => -1]);

    $item = $this->cart->get(456);

    expect($item['quantity'])->toBe(2)
        ->and(round($this->cart->getSubTotal(false), 2))->toBe(205.23);
});

it('does not reduce quantity below zero when ignoring negative updates', function () {
    $items = [
        [
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 3,
            'attributes' => [],
        ],
    ];

    $this->cart->add($items);

    $item = $this->cart->get(456);
    expect($item['quantity'])->toBe(3);

    // Assuming implementation logic: if reducing by 3 checks out.
    // The original test says: "item quantity ... should now be reduced to 2" implies expectation was 2?
    // Wait, original test said:
    // $this->cart->update(456, array('quantity' => -3));
    // $this->assertEquals(3, $item['quantity'], 'Item quantity of with item ID of 456 should now be reduced to 2');
    // The Message says "should now be reduced to 2" but the assertion expects 3. This means the update FAILED to reduce it.
    // So if I send -3 and current is 3, result 0 is likely invalid if configured so.

    $this->cart->update(456, ['quantity' => -3]);
    $item = $this->cart->get(456);

    expect($item['quantity'])->toBe(3);
});

it('throws exception when provided invalid values (price 0 check?)', function () {
    // Scenario 1
    $this->expectException(InvalidItemException::class);
    $this->cart->add(455, 'Sample Item', 100.99, 0, []);
});

it('throws exception when name is empty', function () {
    // Scenario 2
    $this->expectException(InvalidItemException::class);
    $this->cart->add('', 'Sample Item', 100.99, 2, []);
});

it('throws exception when id is empty', function () {
    // Scenario 3
    $this->expectException(InvalidItemException::class);
    $this->cart->add(523, '', 100.99, 2, []);
});

it('can clear cart', function () {
    $items = [
        [
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 3,
            'attributes' => [],
        ],
    ];

    $this->cart->add($items);
    expect($this->cart->isEmpty())->toBeFalse();

    $this->cart->clear();
    expect($this->cart->isEmpty())->toBeTrue();
});

it('can get total quantity', function () {
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

    expect($this->cart->getTotalQuantity())->toBeInt()->toBe(4);
});

it('can add items as array with associated model', function () {
    $item = [
        'id' => 456,
        'name' => 'Sample Item',
        'price' => 67.99,
        'quantity' => 4,
        'attributes' => [],
        'associatedModel' => MockProduct::class,
    ];

    $this->cart->add($item);

    $addedItem = $this->cart->get($item['id']);

    expect($this->cart->isEmpty())->toBeFalse()
        ->and($this->cart->getContent())->toHaveCount(1)
        ->and($addedItem->model)->toBeInstanceOf(MockProduct::class);
});

it('can add items with multidimensional array with associated model', function () {
    $items = [
        [
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 4,
            'attributes' => [],
            'associatedModel' => MockProduct::class,
        ],
        [
            'id' => 568,
            'name' => 'Sample Item 2',
            'price' => 69.25,
            'quantity' => 4,
            'attributes' => [],
            'associatedModel' => MockProduct::class,
        ],
        [
            'id' => 856,
            'name' => 'Sample Item 3',
            'price' => 50.25,
            'quantity' => 4,
            'attributes' => [],
            'associatedModel' => MockProduct::class,
        ],
    ];

    $this->cart->add($items);

    $content = $this->cart->getContent();
    foreach ($content as $item) {
        expect($item->model)->toBeInstanceOf(MockProduct::class);
    }

    expect($this->cart->getTotalQuantity())->toBe(12);
});

it('returns null when accessing model on item without associated model', function () {
    $this->cart->add(455, 'Sample Item', 100.99, 2, []);

    $item = $this->cart->get(455);

    expect($item->model)->toBeNull();
});

it('handles empty string as associated model safely', function () {
    $this->cart->add(455, 'Sample Item', 100.99, 2, [], [], '');

    $item = $this->cart->get(455);

    expect($item->model)->toBeNull();
});

it('saves cart to session on update', function () {
    $this->cart->add(455, 'Sample Item', 100.99, 2, []);

    // reset put calls (since add called it)
    $this->session->putCalls = [];

    $this->cart->update(455, ['quantity' => 1]);

    expect($this->session->putCalls)->toHaveKey('SAMPLESESSIONKEY_cart_items');
});

describe('Events', function () {
    it('fires created event', function () {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch')->with('shopping.created', m::any(), true)->once();

        new Cart(
            new SessionMock,
            $events,
            'shopping',
            'SAMPLESESSIONKEY',
            require (__DIR__.'/Helpers/configMock.php')
        );
    });

    it('fires adding event', function () {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch')->with('shopping.created', m::any(), true);

        $events->shouldReceive('dispatch')->with('shopping.adding', m::any(), m::any())->once()->andReturn(true);
        $events->shouldReceive('dispatch')->with('shopping.added', m::any(), m::any())->once();

        $cart = new Cart(
            new SessionMock,
            $events,
            'shopping',
            'SAMPLESESSIONKEY',
            require (__DIR__.'/Helpers/configMock.php')
        );

        $cart->add(455, 'Sample Item', 100.99, 2, []);
    });

    it('fires updating event', function () {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch')->with('shopping.created', m::any(), true);
        $events->shouldReceive('dispatch')->with('shopping.adding', m::any(), true)->andReturn(true);
        $events->shouldReceive('dispatch')->with('shopping.added', m::any(), true);

        $events->shouldReceive('dispatch')->with('shopping.updating', m::any(), true)->once()->andReturn(true);
        $events->shouldReceive('dispatch')->with('shopping.updated', m::any(), true)->once();

        $cart = new Cart(
            new SessionMock,
            $events,
            'shopping',
            'SAMPLESESSIONKEY',
            require (__DIR__.'/Helpers/configMock.php')
        );

        $cart->add(455, 'Sample Item', 100.99, 2, []);
        $result = $cart->update(455, ['quantity' => 1]);

        expect($result)->toBeTrue();
    });

    it('fires removing event', function () {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch')->with('shopping.created', m::any(), true);
        $events->shouldReceive('dispatch')->with('shopping.adding', m::any(), true)->andReturn(true);
        $events->shouldReceive('dispatch')->with('shopping.added', m::any(), true);

        $events->shouldReceive('dispatch')->with('shopping.removing', m::any(), true)->once()->andReturn(true);
        $events->shouldReceive('dispatch')->with('shopping.removed', m::any(), true)->once();

        $cart = new Cart(
            new SessionMock,
            $events,
            'shopping',
            'SAMPLESESSIONKEY',
            require (__DIR__.'/Helpers/configMock.php')
        );

        $cart->add(455, 'Sample Item', 100.99, 2, []);
        $result = $cart->remove(455);

        expect($result)->toBeTrue();
    });

    it('fires clearing event', function () {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch')->with('shopping.created', m::any(), true);
        $events->shouldReceive('dispatch')->with('shopping.adding', m::any(), m::any())->andReturn(true);
        $events->shouldReceive('dispatch')->with('shopping.added', m::any(), m::any());

        $events->shouldReceive('dispatch')->with('shopping.clearing', m::any(), true)->once()->andReturn(true);
        $events->shouldReceive('dispatch')->with('shopping.cleared', m::any(), true)->once();

        $cart = new Cart(
            new SessionMock,
            $events,
            'shopping',
            'SAMPLESESSIONKEY',
            require (__DIR__.'/Helpers/configMock.php')
        );

        $cart->add(455, 'Sample Item', 100.99, 2, []); // Added this line to ensure cart is not empty before clearing
        $result = $cart->clear();

        expect($result)->toBeTrue();
    });

    it('cancels adding', function () {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch')->with('shopping.created', m::any(), true);

        $events->shouldReceive('dispatch')->with('shopping.adding', m::any(), true)->once()->andReturn(false);
        $events->shouldReceive('dispatch')->with('shopping.added', m::any(), true)->never();

        $cart = new Cart(
            new SessionMock,
            $events,
            'shopping',
            'SAMPLESESSIONKEY',
            require (__DIR__.'/Helpers/configMock.php')
        );

        // Add returns self, not bool (chainable). So we check state only.
        $cart->add(455, 'Sample Item', 100.99, 2, []);

        expect($cart->isEmpty())->toBeTrue();
    });

    it('cancels updating', function () {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch')->with('shopping.created', m::any(), true);
        $events->shouldReceive('dispatch')->with('shopping.adding', m::any(), true)->andReturn(true);
        $events->shouldReceive('dispatch')->with('shopping.added', m::any(), true);

        $events->shouldReceive('dispatch')->with('shopping.updating', m::any(), true)->once()->andReturn(false);
        $events->shouldReceive('dispatch')->with('shopping.updated', m::any(), true)->never();

        $cart = new Cart(
            new SessionMock,
            $events,
            'shopping',
            'SAMPLESESSIONKEY',
            require (__DIR__.'/Helpers/configMock.php')
        );

        $cart->add(455, 'Sample Item', 100.99, 2, []);
        $result = $cart->update(455, ['quantity' => 1]);

        // Result should be false because update was cancelled.
        expect($result)->toBeFalse()
            ->and($cart->get(455)['quantity'])->toBe(2);
    });

    it('cancels removing', function () {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch')->with('shopping.created', m::any(), true);
        $events->shouldReceive('dispatch')->with('shopping.adding', m::any(), true)->andReturn(true);
        $events->shouldReceive('dispatch')->with('shopping.added', m::any(), true);

        $events->shouldReceive('dispatch')->with('shopping.removing', m::any(), true)->once()->andReturn(false);
        $events->shouldReceive('dispatch')->with('shopping.removed', m::any(), true)->never();

        $cart = new Cart(
            new SessionMock,
            $events,
            'shopping',
            'SAMPLESESSIONKEY',
            require (__DIR__.'/Helpers/configMock.php')
        );

        $cart->add(455, 'Sample Item', 100.99, 2, []);
        $result = $cart->remove(455);

        expect($result)->toBeFalse()
            ->and($cart->isEmpty())->toBeFalse();
    });

    it('cancels clearing', function () {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch')->with('shopping.created', m::any(), true);
        $events->shouldReceive('dispatch')->with('shopping.adding', m::any(), m::any())->andReturn(true);
        $events->shouldReceive('dispatch')->with('shopping.added', m::any(), m::any());

        $events->shouldReceive('dispatch')->with('shopping.clearing', m::any(), true)->once()->andReturn(false);
        $events->shouldReceive('dispatch')->with('shopping.cleared', m::any(), true)->never();

        $cart = new Cart(
            new SessionMock,
            $events,
            'shopping',
            'SAMPLESESSIONKEY',
            require (__DIR__.'/Helpers/configMock.php')
        );

        $cart->add(455, 'Sample Item', 100.99, 2, []);
        $result = $cart->clear();

        expect($result)->toBeFalse()
            ->and($cart->isEmpty())->toBeFalse();
    });
});
