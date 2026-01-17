<?php

declare(strict_types=1);

use Mikehins\Cart\Cart;
use Mikehins\Cart\CartCondition;
use Mikehins\Cart\Tests\Helpers\SessionMock;
use Mockery as m;

covers(Cart::class);
covers(CartCondition::class);

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

function fillCart($cart)
{
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

    $cart->add($items);
}

it('subtotal with condition', function () {
    fillCart($this->cart);

    $condition = new CartCondition([
        'name' => 'VAT 12.5%',
        'type' => 'tax',
        'target' => 'subtotal',
        'value' => '-5',
    ]);

    $this->cart->condition($condition);

    expect($this->cart->getSubTotal())->toBe(182.49)
        ->and($this->cart->getTotal())->toBe(182.49);
});

it('total without condition', function () {
    fillCart($this->cart);

    expect($this->cart->getSubTotal())->toBe(187.49)
        ->and($this->cart->getTotal())->toBe(187.49);
});

it('total with condition', function () {
    fillCart($this->cart);

    expect($this->cart->getSubTotal())->toBe(187.49);

    $condition = new CartCondition([
        'name' => 'VAT 12.5%',
        'type' => 'tax',
        'target' => 'total',
        'value' => '12.5%',
    ]);

    $this->cart->condition($condition);

    expect($this->cart->getSubTotal())->toBe(187.49);

    $this->cart->setDecimals(5);
    expect($this->cart->getTotal())->toBe(210.92625);
});

it('total with multiple conditions scenario one', function () {
    fillCart($this->cart);

    $condition1 = new CartCondition([
        'name' => 'VAT 12.5%',
        'type' => 'tax',
        'target' => 'total',
        'value' => '12.5%',
    ]);
    $condition2 = new CartCondition([
        'name' => 'Express Shipping $15',
        'type' => 'shipping',
        'target' => 'total',
        'value' => '+15',
    ]);

    $this->cart->condition($condition1);
    $this->cart->condition($condition2);

    expect($this->cart->getSubTotal())->toBe(187.49);

    $this->cart->setDecimals(5);
    expect($this->cart->getTotal())->toBe(225.92625);
});

it('total with multiple conditions scenario two', function () {
    fillCart($this->cart);

    $condition1 = new CartCondition([
        'name' => 'VAT 12.5%',
        'type' => 'tax',
        'target' => 'total',
        'value' => '12.5%',
    ]);
    $condition2 = new CartCondition([
        'name' => 'Express Shipping $15',
        'type' => 'shipping',
        'target' => 'total',
        'value' => '-15',
    ]);

    $this->cart->condition($condition1);
    $this->cart->condition($condition2);

    expect($this->cart->getSubTotal())->toBe(187.49);

    $this->cart->setDecimals(5);
    expect($this->cart->getTotal())->toBe(195.92625);
});

it('total with multiple conditions scenario three', function () {
    fillCart($this->cart);

    $condition1 = new CartCondition([
        'name' => 'VAT 12.5%',
        'type' => 'tax',
        'target' => 'total',
        'value' => '-12.5%',
    ]);
    $condition2 = new CartCondition([
        'name' => 'Express Shipping $15',
        'type' => 'shipping',
        'target' => 'total',
        'value' => '-15',
    ]);

    $this->cart->condition($condition1);
    $this->cart->condition($condition2);

    expect($this->cart->getSubTotal())->toBe(187.49);

    $this->cart->setDecimals(5);
    expect($this->cart->getTotal())->toBe(149.05375);
});

it('multiple conditions can be added once by array', function () {
    fillCart($this->cart);

    $condition1 = new CartCondition([
        'name' => 'VAT 12.5%',
        'type' => 'tax',
        'target' => 'total',
        'value' => '-12.5%',
    ]);
    $condition2 = new CartCondition([
        'name' => 'Express Shipping $15',
        'type' => 'shipping',
        'target' => 'total',
        'value' => '-15',
    ]);

    $this->cart->condition([$condition1, $condition2]);

    expect($this->cart->getSubTotal())->toBe(187.49);

    $this->cart->setDecimals(5);
    expect($this->cart->getTotal())->toBe(149.05375);
});

it('total with multiple conditions scenario four', function () {
    fillCart($this->cart);

    $condition1 = new CartCondition([
        'name' => 'COUPON LESS 12.5%',
        'type' => 'tax',
        'target' => 'total',
        'value' => '-12.5%',
    ]);
    $condition2 = new CartCondition([
        'name' => 'Express Shipping $15',
        'type' => 'shipping',
        'target' => 'total',
        'value' => '+15',
    ]);

    $this->cart->condition($condition1);
    $this->cart->condition($condition2);

    expect($this->cart->getSubTotal())->toBe(187.49);

    $this->cart->setDecimals(5);
    expect($this->cart->getTotal())->toBe(179.05375);
});

it('add item with condition', function () {
    $condition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'tax',
        'value' => '-5%',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
        'conditions' => $condition1,
    ];

    $this->cart->add($item);

    expect($this->cart->get(456)->getPriceSumWithConditions())->toBe(95.0)
        ->and($this->cart->getSubTotal())->toBe(95.0);
});

it('add item with multiple item conditions in multiple condition instance', function () {
    $itemCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'value' => '-5%',
    ]);
    $itemCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 25.00',
        'type' => 'promo',
        'value' => '-25',
    ]);
    $itemCondition3 = new CartCondition([
        'name' => 'MISC',
        'type' => 'misc',
        'value' => '+10',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
        'conditions' => [$itemCondition1, $itemCondition2, $itemCondition3],
    ];

    $this->cart->add($item);

    expect($this->cart->get(456)->getPriceSumWithConditions())->toBe(80.00)
        ->and($this->cart->getSubTotal())->toBe(80.00);
});

it('add item with multiple item conditions with target omitted', function () {
    $itemCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 25.00',
        'type' => 'promo',
        'value' => '-25',
    ]);
    $itemCondition3 = new CartCondition([
        'name' => 'MISC',
        'type' => 'misc',
        'value' => '+10',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
        'conditions' => [$itemCondition2, $itemCondition3],
    ];

    $this->cart->add($item);

    expect($this->cart->get(456)->getPriceSumWithConditions())->toBe(85.00)
        ->and($this->cart->getSubTotal())->toBe(85.00);
});

it('add item condition', function () {
    $itemCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 25.00',
        'type' => 'promo',
        'value' => '-25',
    ]);
    $coupon101 = new CartCondition([
        'name' => 'COUPON 101',
        'type' => 'coupon',
        'value' => '-5%',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
        'conditions' => [$itemCondition2],
    ];

    $this->cart->add($item);

    expect($this->cart->get($item['id'])['conditions'])->toHaveCount(1);

    $this->cart->addItemCondition($item['id'], $coupon101);

    expect($this->cart->get($item['id'])['conditions'])->toHaveCount(2);
});

it('add item condition restrict negative price', function () {
    $condition = new CartCondition([
        'name' => 'Substract amount but prevent negative value',
        'type' => 'promo',
        'value' => '-25',
    ]);

    $item = [
        'id' => 789,
        'name' => 'Sample Item 1',
        'price' => 20,
        'quantity' => 1,
        'attributes' => [],
        'conditions' => [
            $condition,
        ],
    ];

    $this->cart->add($item);

    expect($this->cart->get($item['id'])->getPriceSumWithConditions())->toBe(0.00);
});

it('get cart condition by condition name', function () {
    $itemCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'target' => 'total',
        'value' => '-5%',
    ]);
    $itemCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 25.00',
        'type' => 'promo',
        'target' => 'total',
        'value' => '-25',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
    ];

    $this->cart->add($item);

    $this->cart->condition([$itemCondition1, $itemCondition2]);

    $condition = $this->cart->getCondition($itemCondition1->getName());

    expect($condition->getName())->toBe('SALE 5%')
        ->and($condition->getTarget())->toBe('total')
        ->and($condition->getType())->toBe('sale')
        ->and($condition->getValue())->toBe('-5%');
});

it('remove cart condition by condition name', function () {
    $itemCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'target' => 'total',
        'value' => '-5%',
    ]);
    $itemCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 25.00',
        'type' => 'promo',
        'target' => 'total',
        'value' => '-25',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
    ];

    $this->cart->add($item);

    $this->cart->condition([$itemCondition1, $itemCondition2]);

    expect($this->cart->getConditions())->toHaveCount(2);

    $this->cart->removeCartCondition('SALE 5%');

    expect($this->cart->getConditions())->toHaveCount(1)
        ->and($this->cart->getConditions()->first()->getName())->toBe('Item Gift Pack 25.00');
});

it('remove item condition by condition name', function () {
    $itemCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'value' => '-5%',
    ]);
    $itemCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 25.00',
        'type' => 'promo',
        'value' => '-25',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
        'conditions' => [$itemCondition1, $itemCondition2],
    ];

    $this->cart->add($item);

    expect($this->cart->get(456)['conditions'])->toHaveCount(2);

    $this->cart->removeItemCondition(456, 'SALE 5%');

    expect($this->cart->get(456)['conditions'])->toHaveCount(1);
});

it('remove item condition by condition name scenario two', function () {
    $itemCondition = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'value' => '-5%',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
        'conditions' => $itemCondition,
    ];

    $this->cart->add($item);

    expect($this->cart->get(456)['conditions'])->not->toBeEmpty();

    $this->cart->removeItemCondition(456, 'SALE 5%');

    expect($this->cart->get(456)['conditions'])->toBeEmpty();
});

it('clear item conditions', function () {
    $itemCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'value' => '-5%',
    ]);
    $itemCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 25.00',
        'type' => 'promo',
        'value' => '-25',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
        'conditions' => [$itemCondition1, $itemCondition2],
    ];

    $this->cart->add($item);

    expect($this->cart->get(456)['conditions'])->toHaveCount(2);

    $this->cart->clearItemConditions(456);

    expect($this->cart->get(456)['conditions'])->toHaveCount(0);
});

it('clear cart conditions', function () {
    $itemCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'target' => 'total',
        'value' => '-5%',
    ]);
    $itemCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 25.00',
        'type' => 'promo',
        'target' => 'total',
        'value' => '-25',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
    ];

    $this->cart->add($item);

    $this->cart->condition([$itemCondition1, $itemCondition2]);

    expect($this->cart->getConditions())->toHaveCount(2);

    $this->cart->clearCartConditions();

    expect($this->cart->getConditions())->toHaveCount(0);
});

it('get calculated value of a condition', function () {
    $cartCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'target' => 'total',
        'value' => '-5%',
    ]);
    $cartCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 25.00',
        'type' => 'promo',
        'target' => 'total',
        'value' => '-25',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
    ];

    $this->cart->add($item);

    $this->cart->condition([$cartCondition1, $cartCondition2]);

    $subTotal = $this->cart->getSubTotal();

    expect($subTotal)->toBe(100.0);

    $cond1 = $this->cart->getCondition('SALE 5%');
    expect($cond1->getCalculatedValue($subTotal))->toBe(5.0);

    $conditions = $this->cart->getConditions();
    expect($conditions['SALE 5%']->getCalculatedValue($subTotal))->toBe(5.0)
        ->and($conditions['Item Gift Pack 25.00']->getCalculatedValue($subTotal))->toBe(25.0);
});

it('get conditions by type', function () {
    $cartCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'target' => 'total',
        'value' => '-5%',
    ]);
    $cartCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 25.00',
        'type' => 'promo',
        'target' => 'total',
        'value' => '-25',
    ]);
    $cartCondition3 = new CartCondition([
        'name' => 'Item Less 8%',
        'type' => 'promo',
        'target' => 'total',
        'value' => '-8%',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
    ];

    $this->cart->add($item);

    $this->cart->condition([$cartCondition1, $cartCondition2, $cartCondition3]);

    $promoConditions = $this->cart->getConditionsByType('promo');

    expect($promoConditions->count())->toBe(2);
});

it('remove conditions by type', function () {
    $cartCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'target' => 'total',
        'value' => '-5%',
    ]);
    $cartCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 20',
        'type' => 'promo',
        'target' => 'total',
        'value' => '-25',
    ]);
    $cartCondition3 = new CartCondition([
        'name' => 'Item Less 8%',
        'type' => 'promo',
        'target' => 'total',
        'value' => '-8%',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
    ];

    $this->cart->add($item);

    $this->cart->condition([$cartCondition1, $cartCondition2, $cartCondition3]);

    $this->cart->removeConditionsByType('promo');

    expect($this->cart->getConditions()->count())->toBe(1);
});

it('add cart condition without condition attributes', function () {
    $cartCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'target' => 'total',
        'value' => '-5%',
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
    ];

    $this->cart->add($item);

    $this->cart->condition([$cartCondition1]);

    $condition = $this->cart->getCondition('SALE 5%');
    expect($condition->getName())->toBe('SALE 5%');

    $conditionAttribute = $condition->getAttributes();
    expect($conditionAttribute)->toBeArray();
});

it('add cart condition with condition attributes', function () {
    $cartCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'target' => 'total',
        'value' => '-5%',
        'attributes' => [
            'description' => 'october fest promo sale',
            'sale_start_date' => '2015-01-20',
            'sale_end_date' => '2015-01-30',
        ],
    ]);

    $item = [
        'id' => 456,
        'name' => 'Sample Item 1',
        'price' => 100,
        'quantity' => 1,
        'attributes' => [],
    ];

    $this->cart->add($item);

    $this->cart->condition([$cartCondition1]);

    $condition = $this->cart->getCondition('SALE 5%');
    expect($condition->getName())->toBe('SALE 5%');

    $conditionAttributes = $condition->getAttributes();
    expect($conditionAttributes)->toBeArray()
        ->and($conditionAttributes)->toHaveKey('description')
        ->and($conditionAttributes)->toHaveKey('sale_start_date')
        ->and($conditionAttributes)->toHaveKey('sale_end_date')
        ->and($conditionAttributes['description'])->toBe('october fest promo sale');
});

it('get order from condition', function () {
    $cartCondition1 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'target' => 'total',
        'value' => '-5%',
        'order' => 2,
    ]);
    $cartCondition2 = new CartCondition([
        'name' => 'Item Gift Pack 20',
        'type' => 'promo',
        'target' => 'total',
        'value' => '-25',
        'order' => '3',
    ]);
    $cartCondition3 = new CartCondition([
        'name' => 'Item Less 8%',
        'type' => 'tax',
        'target' => 'total',
        'value' => '-8%',
        'order' => 'first',
    ]);

    expect($cartCondition1->getOrder())->toBe(2)
        ->and($cartCondition2->getOrder())->toBe(3)
        ->and($cartCondition3->getOrder())->toBe(0);

    $this->cart->condition($cartCondition1);
    $this->cart->condition($cartCondition2);
    $this->cart->condition($cartCondition3);

    $conditions = $this->cart->getConditions();

    expect($conditions->shift()->getType())->toBe('sale')
        ->and($conditions->shift()->getType())->toBe('promo')
        ->and($conditions->shift()->getType())->toBe('tax');
});

it('condition ordering', function () {
    $cartCondition1 = new CartCondition([
        'name' => 'TAX',
        'type' => 'tax',
        'target' => 'total',
        'value' => '-8%',
        'order' => 5,
    ]);
    $cartCondition2 = new CartCondition([
        'name' => 'SALE 5%',
        'type' => 'sale',
        'target' => 'total',
        'value' => '-5%',
        'order' => 2,
    ]);
    $cartCondition3 = new CartCondition([
        'name' => 'Item Gift Pack 20',
        'type' => 'promo',
        'target' => 'total',
        'value' => '-25',
        'order' => 1,
    ]);

    fillCart($this->cart);

    $this->cart->condition($cartCondition1);
    $this->cart->condition($cartCondition2);
    $this->cart->condition($cartCondition3);

    expect($this->cart->getConditions()->first()->getName())->toBe('Item Gift Pack 20')
        ->and($this->cart->getConditions()->last()->getName())->toBe('TAX');
});
