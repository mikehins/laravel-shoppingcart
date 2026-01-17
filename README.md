# Laravel Shopping Cart

[![Latest Stable Version](https://poser.pugx.org/mikehins/laravel-shoppingcart/v)](https://packagist.org/packages/mikehins/laravel-shoppingcart)
[![License](https://poser.pugx.org/mikehins/laravel-shoppingcart/license.svg)](https://packagist.org/packages/mikehins/laravel-shoppingcart)

A modern, strictly typed, and performance-oriented Shopping Cart implementation for Laravel.

## 📊 Quality Metrics

| Metric | Status |
|--------|--------|
| **Tests** | 71 passing |
| **Type Coverage** | 97.1% |
| **Mutation Score** | 60.5% |
| **PHPStan Level** | 5 |
| **Code Style** | Laravel Pint |

## 🌟 Modernization & Attribution

This package is a modernized fork of the original [darryldecode/laravelshoppingcart](https://github.com/darryldecode/laravelshoppingcart). The original package provided a robust foundation but has effectively reached end-of-life for modern applications.

**Why this fork exists:**
- **Laravel 12+ / PHP 8.3+ Support:** Rebuilt to support the latest ecosystems without legacy baggage.
- **Strict Typing:** All classes use `declare(strict_types=1)` with typed properties and return types.
- **PHPStan Validated:** Static analysis ensures contract compliance and reduces runtime errors.
- **Pest Test Suite:** The entire test suite has been migrated from PHPUnit to Pest v3 for better readability.
- **Mutation Testing:** Core logic validated with Pest's mutation testing plugin.

## 🚀 Installation

Install the package via Composer:

```bash
composer require mikehins/laravel-shoppingcart
```

The service provider and facade are automatically discovered.

### Configuration (Optional)

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Mikehins\Cart\CartServiceProvider" --tag="config"
```

## 🛠 Basic Usage

### Adding items

```php
// Simple addition
cart()->add([
    'id' => 456, // Unique ID per item
    'name' => 'Sample Item',
    'price' => 50.00,
    'quantity' => 1,
    'attributes' => [
        'size' => 'L',
        'color' => 'Red'
    ]
]);

// With conditions
cart()->add([
    'id' => 456,
    'name' => 'Sample Item',
    'price' => 50.00,
    'quantity' => 1,
    'attributes' => [],
    'conditions' => $condition // CartCondition instance or array of them
]);
```

### Retrieving Cart Content

```php
$cartCollection = cart()->getContent();
```

### Updating Items

```php
cart()->update(456, [
    'name' => 'New Item Name', // New name
    'price' => 98.67, // New price
]);

// Relative quantity update (add 2 to existing)
cart()->update(456, [
    'quantity' => 2,
]);

// Absolute quantity update (set quantity to 4)
cart()->update(456, [
    'quantity' => [
        'relative' => false,
        'value' => 4
    ],
]);
```

### Removing Items

```php
cart()->remove(456);
```

### Clearing Cart

```php
cart()->clear();
```

## 🏷 Conditions (Coupons, Taxes, Shipping)

Conditions can be applied to the **whole cart** or **specific items**.

### Cart-Wide Conditions

Target either `subtotal` or `total`.

```php
use Mikehins\Cart\CartCondition;

$condition = new CartCondition([
     'name' => 'VAT 12.5%',
     'type' => 'tax',
     'target' => 'subtotal', // Applied when getSubTotal() is called
     'value' => '12.5%', // Can be percentage string or absolute '-10'
     'attributes' => [
     	'description' => 'Value added tax',
     ]
]);

cart()->condition($condition);
```

### Item-Specific Conditions

Conditions applied to a specific item.

```php
$condition = new CartCondition([
    'name' => 'SALE 5%',
    'type' => 'sale',
    'value' => '-5%',
]);

cart()->add([
    'id' => 456,
    'name' => 'Sample Item',
    'price' => 100,
    'quantity' => 1,
    'conditions' => $condition
]);
```

## 🎯 Events

The cart fires events for all major operations, allowing you to hook into the cart lifecycle:

| Event | Description |
|-------|-------------|
| `cart.created` | Fired when a new cart instance is created |
| `cart.adding` | Fired before an item is added (return `false` to cancel) |
| `cart.added` | Fired after an item is added |
| `cart.updating` | Fired before an item is updated (return `false` to cancel) |
| `cart.updated` | Fired after an item is updated |
| `cart.removing` | Fired before an item is removed (return `false` to cancel) |
| `cart.removed` | Fired after an item is removed |
| `cart.clearing` | Fired before cart is cleared (return `false` to cancel) |
| `cart.cleared` | Fired after cart is cleared |

## 🧪 Testing

We use Pest for testing. The test suite includes:

- **Unit Tests**: Core cart functionality
- **Mutation Testing**: Validates test quality by ensuring tests catch code changes
- **Type Coverage**: Ensures proper type declarations across the codebase
- **Static Analysis**: PHPStan validation

To run the full test suite:

```bash
composer test
```

This runs:
1. `pest --parallel` - Unit tests
2. `pest --mutate --parallel` - Mutation testing
3. `pest --type-coverage --min=97 --parallel` - Type coverage check
4. `pint --preset laravel` - Code style fixing
5. `rector process --dry-run` - Automated refactoring check
6. `phpstan analyse` - Static analysis

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
