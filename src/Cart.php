<?php

declare(strict_types=1);

namespace Mikehins\Cart;

use Exception;
use Mikehins\Cart\Exceptions\InvalidConditionException;
use Mikehins\Cart\Exceptions\InvalidItemException;
use Mikehins\Cart\Exceptions\UnknownModelException;
use Mikehins\Cart\Helpers\Helpers;
use Mikehins\Cart\Validators\CartItemValidator;

final class Cart
{
    public int $decimals;

    public string $dec_point;

    public string $thousands_sep;

    private string $sessionKeyCartItems;

    private string $sessionKeyCartConditions;

    private string|int|null $currentItemId = null;

    public function __construct(
        private readonly mixed $session,
        private readonly mixed $events,
        private readonly string $instanceName,
        private string $sessionKey,
        private readonly array $config
    ) {
        $this->sessionKeyCartItems = $this->sessionKey.'_cart_items';
        $this->sessionKeyCartConditions = $this->sessionKey.'_cart_conditions';

        $this->fireEvent('created');
    }

    public function session(string $sessionKey): self
    {
        if ($sessionKey === '' || $sessionKey === '0') {
            throw new Exception('Session key is required.');
        }

        $this->sessionKey = $sessionKey;
        $this->sessionKeyCartItems = $this->sessionKey.'_cart_items';
        $this->sessionKeyCartConditions = $this->sessionKey.'_cart_conditions';

        return $this;
    }

    public function getInstanceName(): string
    {
        return $this->instanceName;
    }

    public function get(string|int $itemId): mixed
    {
        return $this->getContent()->get($itemId);
    }

    public function has(string|int $itemId): bool
    {
        return $this->getContent()->has($itemId);
    }

    /**
     * @throws InvalidItemException
     */
    public function add(string|int|array $id, ?string $name = null, float|string|null $price = null, int|float|string|null $quantity = null, array $attributes = [], CartCondition|array $conditions = [], ?string $associatedModel = null): self
    {
        if (is_array($id)) {
            $items = Helpers::isMultiArray($id) ? $id : [$id];

            foreach ($items as $item) {
                $this->add(
                    id: $item['id'] ?? throw new InvalidItemException('Item ID is required.'),
                    name: $item['name'] ?? null,
                    price: $item['price'] ?? null,
                    quantity: $item['quantity'] ?? null,
                    attributes: $item['attributes'] ?? [],
                    conditions: $item['conditions'] ?? [],
                    associatedModel: $item['associatedModel'] ?? null
                );
            }

            return $this;
        }

        $data = [
            'id' => $id,
            'name' => $name,
            'price' => Helpers::normalizePrice($price),
            'quantity' => $quantity,
            'attributes' => new ItemAttributeCollection($attributes),
            'conditions' => $conditions,
        ];

        if (! in_array($associatedModel, [null, '', '0'], true)) {
            $data['associatedModel'] = $associatedModel;
        }

        $item = $this->validate($data);

        $cart = $this->getContent();

        if ($cart->has($id)) {
            $this->update($id, $item);
        } else {
            $this->addRow($id, $item);
        }

        $this->currentItemId = $id;

        return $this;
    }

    public function update(string|int $id, array $data): bool
    {
        if ($this->fireEvent('updating', $data) === false) {
            return false;
        }

        $cart = $this->getContent();

        if (! $cart->has($id)) {
            return false;
        }

        $item = $cart->pull($id);

        foreach ($data as $key => $value) {
            if ($key === 'quantity') {
                $item = $this->updateQuantityField($item, $value);

                continue;
            }

            if ($key === 'attributes') {
                $item[$key] = new ItemAttributeCollection($value);

                continue;
            }

            $item[$key] = $value;
        }

        $cart->put($id, $item);

        $this->save($cart);

        $this->fireEvent('updated', $item);

        return true;
    }

    public function addItemCondition(string|int $productId, CartCondition|array $itemCondition): self
    {
        $product = $this->get($productId);

        if (! $product) {
            return $this;
        }

        if (! ($itemCondition instanceof CartCondition)) {
            return $this;
        }

        $conditions = $product['conditions'];

        if (is_array($conditions)) {
            $conditions[] = $itemCondition;
        } else {
            $conditions = $itemCondition;
        }

        $this->update($productId, [
            'conditions' => $conditions,
        ]);

        return $this;
    }

    public function remove(string|int $id): bool
    {
        if ($this->fireEvent('removing', $id) === false) {
            return false;
        }

        $cart = $this->getContent();

        $cart->forget($id);

        $this->save($cart);

        $this->fireEvent('removed', $id);

        return true;
    }

    public function clear(): bool
    {
        if ($this->fireEvent('clearing') === false) {
            return false;
        }

        $this->session->put(
            $this->sessionKeyCartItems,
            []
        );

        $this->fireEvent('cleared');

        return true;
    }

    /**
     * @throws InvalidConditionException
     */
    public function condition(CartCondition|array $condition): self
    {
        if (is_array($condition)) {
            foreach ($condition as $c) {
                $this->condition($c);
            }

            return $this;
        }

        $conditions = $this->getConditions();

        if ($condition->getOrder() === 0) {
            $last = $conditions->last();
            $condition->setOrder(is_null($last) ? 1 : $last->getOrder() + 1);
        }

        $conditions->put($condition->getName(), $condition);

        $conditions = $conditions->sortBy(fn (CartCondition $condition, string $key): int => $condition->getOrder());

        $this->saveConditions($conditions);

        return $this;
    }

    public function getConditions(): CartConditionCollection
    {
        return new CartConditionCollection($this->session->get($this->sessionKeyCartConditions));
    }

    public function getCondition(string $conditionName): ?CartCondition
    {
        return $this->getConditions()->get($conditionName);
    }

    public function getConditionsByType(string $type): CartConditionCollection
    {
        return $this->getConditions()->filter(fn (CartCondition $condition) => $condition->getType() === $type);
    }

    public function removeConditionsByType(string $type): void
    {
        $this->getConditionsByType($type)->each(function (CartCondition $condition): void {
            $this->removeCartCondition($condition->getName());
        });
    }

    public function removeCartCondition(string $conditionName): void
    {
        $conditions = $this->getConditions();

        $conditions->pull($conditionName);

        $this->saveConditions($conditions);
    }

    public function removeItemCondition(string|int $itemId, string $conditionName): bool
    {
        $item = $this->getContent()->get($itemId);

        if (! $item) {
            return false;
        }

        if (! $this->itemHasConditions($item)) {
            return true;
        }

        $conditions = $item['conditions'];

        if (is_array($conditions)) {
            $item['conditions'] = array_filter($conditions, fn ($condition) => $condition->getName() !== $conditionName);
        } elseif ($conditions instanceof CartCondition && $conditions->getName() === $conditionName) {
            $item['conditions'] = [];
        }

        $this->update($itemId, [
            'conditions' => $item['conditions'],
        ]);

        return true;
    }

    public function clearItemConditions(string|int $itemId): bool
    {
        if (! $this->has($itemId)) {
            return false;
        }

        $this->update($itemId, [
            'conditions' => [],
        ]);

        return true;
    }

    public function clearCartConditions(): void
    {
        $this->session->put(
            $this->sessionKeyCartConditions,
            []
        );
    }

    public function getSubTotalWithoutConditions(bool $formatted = true): float|string
    {
        $cart = $this->getContent();

        $sum = $cart->sum(fn (ItemCollection $item): float|string => $item->getPriceSum());

        return Helpers::formatValue((float) $sum, $formatted, $this->config);
    }

    public function getSubTotal(bool $formatted = true): float|string
    {
        $cart = $this->getContent();

        $sum = $cart->sum(fn (ItemCollection $item) => $item->getPriceSumWithConditions(false));

        $conditions = $this
            ->getConditions()
            ->filter(fn (CartCondition $cond) => $cond->getTarget() === 'subtotal');

        if (! $conditions->count()) {
            return Helpers::formatValue((float) $sum, $formatted, $this->config);
        }

        $newTotal = 0.00;
        $process = 0;

        $conditions->each(function (CartCondition $cond) use ($sum, &$newTotal, &$process): void {
            $toBeCalculated = ($process > 0) ? $newTotal : $sum;

            $newTotal = $cond->applyCondition($toBeCalculated);

            $process++;
        });

        return Helpers::formatValue($newTotal, $formatted, $this->config);
    }

    public function getTotal(): float|string
    {
        $subTotal = $this->getSubTotal(false);

        $newTotal = 0.00;

        $process = 0;

        $conditions = $this
            ->getConditions()
            ->filter(fn (CartCondition $cond) => $cond->getTarget() === 'total');

        if (! $conditions->count()) {
            return Helpers::formatValue($subTotal, $this->config['format_numbers'], $this->config);
        }

        $conditions
            ->each(function (CartCondition $cond) use ($subTotal, &$newTotal, &$process): void {
                $toBeCalculated = ($process > 0) ? $newTotal : $subTotal;

                $newTotal = $cond->applyCondition($toBeCalculated);

                $process++;
            });

        return Helpers::formatValue($newTotal, $this->config['format_numbers'], $this->config);
    }

    public function getTotalQuantity(): int
    {
        $items = $this->getContent();

        if ($items->isEmpty()) {
            return 0;
        }

        return (int) $items->sum(fn (ItemCollection $item): mixed => $item['quantity']);
    }

    public function getContent(): CartCollection
    {
        return (new CartCollection($this->session->get($this->sessionKeyCartItems)))->reject(fn (mixed $item): bool => ! ($item instanceof ItemCollection));
    }

    public function isEmpty(): bool
    {
        return $this->getContent()->isEmpty();
    }

    /**
     * @throws UnknownModelException
     */
    public function associate(string $model): self
    {
        if (! class_exists($model)) {
            throw new UnknownModelException("The supplied model {$model} does not exist.");
        }

        $cart = $this->getContent();

        $item = $cart->pull($this->currentItemId);

        $item['associatedModel'] = $model;

        $cart->put($this->currentItemId, new ItemCollection($item, $this->config));

        $this->save($cart);

        return $this;
    }

    public function setDecimals(int $decimals): void
    {
        $this->decimals = $decimals;
    }

    public function setDecPoint(string $decPoint): void
    {
        $this->dec_point = $decPoint;
    }

    public function setThousandsSep(string $thousandsSep): void
    {
        $this->thousands_sep = $thousandsSep;
    }

    private function updateQuantityField(ItemCollection $item, mixed $value): ItemCollection
    {
        if (is_array($value)) {
            return (isset($value['relative']) && (bool) $value['relative'])
                ? $this->updateQuantityRelative($item, 'quantity', $value['value'])
                : $this->updateQuantityNotRelative($item, 'quantity', $value['value']);
        }

        return $this->updateQuantityRelative($item, 'quantity', $value);
    }

    /**
     * @throws InvalidItemException
     */
    private function validate(array $item): array
    {
        $rules = [
            'id' => 'required',
            'price' => 'required|numeric',
            'quantity' => 'required|numeric|min:0.1',
            'name' => 'required',
        ];

        $validator = CartItemValidator::make($item, $rules);

        if ($validator->fails()) {
            throw new InvalidItemException($validator->messages()->first());
        }

        return $item;
    }

    private function addRow(string|int $id, array $item): bool
    {
        if ($this->fireEvent('adding', $item) === false) {
            return false;
        }

        $cart = $this->getContent();

        $cart->put($id, new ItemCollection($item, $this->config));

        $this->save($cart);

        $this->fireEvent('added', $item);

        return true;
    }

    private function save(CartCollection $cart): void
    {
        $this->session->put($this->sessionKeyCartItems, $cart);
    }

    private function saveConditions(CartConditionCollection $conditions): void
    {
        $this->session->put($this->sessionKeyCartConditions, $conditions);
    }

    private function itemHasConditions(mixed $item): bool
    {
        if (! isset($item['conditions'])) {
            return false;
        }

        if (is_array($item['conditions'])) {
            return count($item['conditions']) > 0;
        }

        return $item['conditions'] instanceof CartCondition;
    }

    private function updateQuantityRelative(mixed $item, string $key, mixed $value): mixed
    {
        if (preg_match('/\-/', (string) $value) === 1) {
            $value = (int) str_replace('-', '', (string) $value);

            if (($item[$key] - $value) > 0) {
                $item[$key] -= $value;
            }
        } elseif (preg_match('/\+/', (string) $value) === 1) {
            $item[$key] += (int) str_replace('+', '', (string) $value);
        } else {
            $item[$key] += (int) $value;
        }

        return $item;
    }

    private function updateQuantityNotRelative(mixed $item, string $key, mixed $value): mixed
    {
        $item[$key] = (int) $value;

        return $item;
    }

    private function fireEvent(string $name, mixed $value = []): mixed
    {
        return $this->events->dispatch($this->getInstanceName().'.'.$name, array_values([$value, $this]), true);
    }
}
