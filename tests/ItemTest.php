<?php

declare(strict_types=1);

use Mikehins\Cart\Cart;
use Mikehins\Cart\CartCondition;
use Mikehins\Cart\ItemAttributeCollection;
use Mikehins\Cart\ItemCollection;
use Mikehins\Cart\Tests\Helpers\MockProduct;
use Mikehins\Cart\Tests\Helpers\SessionMock;
use Mockery as m;

covers(ItemCollection::class);
covers(ItemAttributeCollection::class);

beforeEach(function () {
    $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
    $events->shouldReceive('dispatch');

    $this->cart = new Cart(
        new SessionMock,
        $events,
        'shopping',
        'SAMPLESESSIONKEY',
        require (__DIR__.'/Helpers/configMock.php')
    );
});

afterEach(function () {
    m::close();
});

it('item get sum price using property', function () {
    $this->cart->add(455, 'Sample Item', 100.99, 2, []);

    $item = $this->cart->get(455);

    expect($item->getPriceSum())->toBe(201.98);
});

it('item get sum price using array style', function () {
    $this->cart->add(455, 'Sample Item', 100.99, 2, []);

    $item = $this->cart->get(455);

    expect($item->getPriceSum())->toBe(201.98);
});

it('item get conditions empty', function () {
    $this->cart->add(455, 'Sample Item', 100.99, 2, []);

    $item = $this->cart->get(455);

    expect($item->getConditions())->toBeEmpty();
});

it('item get conditions with conditions', function () {
    $itemCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'target' => 'item',
        'value' => '-5%',
    ]);

    $itemCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 25.00',
        'type' => 'promo',
        'target' => 'item',
        'value' => '-25',
    ]);

    $this->cart->add(455, 'Sample Item', 100.99, 2, [], [$itemCondition1, $itemCondition2]);

    $item = $this->cart->get(455);

    expect($item->getConditions())->toHaveCount(2);
});

it('item associate model', function () {
    $this->cart->add(455, 'Sample Item', 100.99, 2, [])->associate(MockProduct::class);

    $item = $this->cart->get(455);

    expect($item->associatedModel)->toBe(MockProduct::class);
});

it('it will throw an exception when a non existing model is being associated', function () {
    $this->expectException(Mikehins\Cart\Exceptions\UnknownModelException::class);
    $this->expectExceptionMessage('The supplied model SomeModel does not exist.');

    $this->cart->add(1, 'Test item', 1, 10.00)->associate('SomeModel');
});

it('item get model', function () {
    $this->cart->add(455, 'Sample Item', 100.99, 2, [])->associate(MockProduct::class);

    $item = $this->cart->get(455);

    expect($item->model)->toBeInstanceOf(MockProduct::class)
        ->and($item->model->name)->toBe('Sample Item')
        ->and($item->model->id)->toBe(455);
});

it('item get model will return null if it has no model', function () {
    $this->cart->add(455, 'Sample Item', 100.99, 2, []);

    $item = $this->cart->get(455);

    expect($item->model)->toBeNull();
});
