<?php

declare(strict_types=1);

namespace Mikehins\Cart;

use Illuminate\Support\Collection;
use Mikehins\Cart\Helpers\Helpers;

/**
 * @property float|string $price
 * @property int $quantity
 * @property array $conditions
 */
final class ItemCollection extends Collection
{
    public function __construct(
        mixed $items,
        protected readonly array $config = []
    ) {
        parent::__construct($items);
    }

    /**
     * @param  string|int  $key
     */
    public function __get(mixed $key): mixed
    {
        if ($this->has($key) || $key === 'model') {
            return $this->get($key) ?? $this->getAssociatedModel();
        }

        return null;
    }

    public function getPriceSum(): float|string
    {
        return Helpers::formatValue((float) $this->price * (int) $this->quantity, $this->config['format_numbers'] ?? false, $this->config);
    }

    public function hasConditions(): bool
    {
        $conditions = $this->get('conditions');

        if (is_array($conditions)) {
            return $conditions !== [];
        }

        return $conditions instanceof CartCondition;
    }

    public function getConditions(): mixed
    {
        return $this->get('conditions', []);
    }

    public function getPriceWithConditions(bool $formatted = true): float|string
    {
        $originalPrice = (float) $this->price;

        if (! $this->hasConditions()) {
            return Helpers::formatValue($originalPrice, $formatted, $this->config);
        }

        $conditions = $this->getConditions();
        $conditions = is_array($conditions) ? $conditions : [$conditions];

        $newPrice = array_reduce($conditions, fn (float $price, CartCondition $condition): float => $condition->applyCondition($price), $originalPrice);

        return Helpers::formatValue($newPrice, $formatted, $this->config);
    }

    public function getPriceSumWithConditions(bool $formatted = true): float|string
    {
        return Helpers::formatValue((float) $this->getPriceWithConditions(false) * (int) $this->quantity, $formatted, $this->config);
    }

    protected function getAssociatedModel(): mixed
    {
        if (! $this->has('associatedModel')) {
            return null;
        }

        $associatedModel = $this->get('associatedModel');

        return with(new $associatedModel)->find($this->get('id'));
    }
}
