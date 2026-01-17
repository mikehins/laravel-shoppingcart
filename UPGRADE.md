# Upgrade Guide

## Upgrading from darryldecode/laravelshoppingcart

This package is a modernized fork of `darryldecode/laravelshoppingcart`. If you're migrating from the original package, follow this guide.

### Step 1: Update Composer

Remove the old package and install this one:

```bash
composer remove darryldecode/laravel-shoppingcart
composer require mikehins/laravel-shoppingcart
```

### Step 2: Update Namespace References

Replace all namespace references in your codebase:

| Old Namespace | New Namespace |
|---------------|---------------|
| `Darryldecode\Cart\Cart` | `Mikehins\Cart\Cart` |
| `Darryldecode\Cart\CartCondition` | `Mikehins\Cart\CartCondition` |
| `Darryldecode\Cart\Facades\CartFacade` | `Mikehins\Cart\Facades\CartFacade` |
| `Darryldecode\Cart\CartServiceProvider` | `Mikehins\Cart\CartServiceProvider` |

### Step 3: Update Configuration

If you published the configuration file, update it:

```bash
php artisan vendor:publish --provider="Mikehins\Cart\CartServiceProvider" --tag="config" --force
```

The configuration file name remains `shopping_cart.php`.

### Step 4: Breaking Changes

#### Strict Types

All classes now use `declare(strict_types=1)`. This means:

- **Price must be numeric**: Passing a non-numeric string will throw a `TypeError`.
- **Quantity must be numeric**: Same as above.
- **ID must be string or int**: Arrays or objects are not accepted.

#### Method Return Types

Some methods now have explicit return types:

| Method | Old Return | New Return |
|--------|------------|------------|
| `update()` | `mixed` | `bool` |
| `remove()` | `mixed` | `bool` |
| `clear()` | `mixed` | `bool` |

These methods now return `false` if the operation was cancelled by an event listener.

#### Event Cancellation

Event listeners can now cancel operations by returning `false`:

```php
Event::listen('cart.adding', function ($item) {
    if ($item['price'] < 0) {
        return false; // Prevents adding the item
    }
});
```

### Step 5: Verify Your Code

Run your test suite to ensure compatibility:

```bash
php artisan test
```

## PHP Version Requirements

| Package Version | PHP Version | Laravel Version |
|-----------------|-------------|-----------------|
| 1.x | 8.3+ | 11.x, 12.x |

## Getting Help

If you encounter issues during migration, please open an issue on the [GitHub repository](https://github.com/mikehins/laravel-shoppingcart).
