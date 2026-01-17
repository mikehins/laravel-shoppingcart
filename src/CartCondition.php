<?php

declare(strict_types=1);

namespace Mikehins\Cart;

use Mikehins\Cart\Exceptions\InvalidConditionException;
use Mikehins\Cart\Helpers\Helpers;
use Mikehins\Cart\Validators\CartConditionValidator;

final class CartCondition
{
    public ?float $parsedRawValue = null;

    public function __construct(private array $args)
    {
        if (Helpers::isMultiArray($this->args)) {
            throw new InvalidConditionException('Multi dimensional array is not supported.');
        }

        $this->validate($this->args);
    }

    public function getTarget(): ?string
    {
        return $this->args['target'] ?? null;
    }

    public function getName(): string
    {
        return $this->args['name'];
    }

    public function getType(): string
    {
        return $this->args['type'];
    }

    public function getAttributes(): array
    {
        return $this->args['attributes'] ?? [];
    }

    public function getValue(): mixed
    {
        return $this->args['value'];
    }

    public function setOrder(int $order = 1): void
    {
        $this->args['order'] = $order;
    }

    public function getOrder(): int
    {
        return isset($this->args['order']) && is_numeric($this->args['order']) ? (int) $this->args['order'] : 0;
    }

    public function applyCondition(float|int $totalOrSubTotalOrPrice): float
    {
        $value = $this->getValue();
        $isPercentage = $this->valueIsPercentage($value);
        $cleanValue = Helpers::normalizePrice($this->cleanValue($value));

        $this->parsedRawValue = $isPercentage ? $totalOrSubTotalOrPrice * ($cleanValue / 100) : $cleanValue;

        if ($this->valueIsToBeSubtracted($value)) {
            $result = $totalOrSubTotalOrPrice - $this->parsedRawValue;
        } else {
            $result = $totalOrSubTotalOrPrice + $this->parsedRawValue;
        }

        return $result < 0 ? 0.00 : $result;
    }

    public function getCalculatedValue(float|int $totalOrSubTotalOrPrice): ?float
    {
        $this->applyCondition($totalOrSubTotalOrPrice);

        return $this->parsedRawValue;
    }

    private function valueIsPercentage(mixed $value): bool
    {
        return preg_match('/%/', (string) $value) === 1;
    }

    private function valueIsToBeSubtracted(mixed $value): bool
    {
        return preg_match('/\-/', (string) $value) === 1;
    }

    private function cleanValue(mixed $value): string
    {
        return str_replace(['%', '-', '+'], '', (string) $value);
    }

    private function validate(array $args): void
    {
        $rules = [
            'name' => 'required',
            'type' => 'required',
            'value' => 'required',
        ];

        $validator = CartConditionValidator::make($args, $rules);

        if ($validator->fails()) {
            throw new InvalidConditionException($validator->messages()->first());
        }
    }
}
