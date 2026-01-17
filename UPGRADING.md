# Upgrading to mikehins/laravel-shoppingcart

## 1. Overview

This is a **major, breaking upgrade** from `darryldecode/laravelshoppingcart`.
The package has been completely modernized, effectively rebooting the project.

Key changes:
- **Requirement**: Laravel 12.0+ and PHP 8.3+
- **Namespace**: Changed from `Darryldecode\Cart` to `Mikehins\Cart`
- **Strictness**: Fully typed codebase with strict type checks (PHPStan Max)

This is **not** a drop-in replacement. You must update your code to support the new namespace and requirements.

## 2. Supported Versions

| Software | Supported Version |
|----------|------------------|
| Laravel  | 12.0+            |
| PHP      | 8.3+             |

**Dropped Support:**
- Laravel < 12
- PHP < 8.3

## 3. Namespace Change (CRITICAL)

The namespace has been completely renamed.  
There are **no backward compatibility aliases**.

### Migration Example

**Before:**
```php
use Darryldecode\Cart\Cart;
use Darryldecode\Cart\CartCondition;
```

**After:**
```php
use Mikehins\Cart\Cart;
use Mikehins\Cart\CartCondition;
```

You must perform a global search and replace in your codebase:
- Find: `Darryldecode\Cart`
- Replace: `Mikehins\Cart`

## 4. Composer & Installation Changes

Remove the old package and install the new one.

```bash
composer remove darryldecode/laravel-shoppingcart
composer require mikehins/laravel-shoppingcart
```

**Constraints:**
```json
"require": {
    "php": "^8.3",
    "illuminate/support": "^12.0"
}
```

## 5. Code-Level Breaking Changes

### Exceptions
All exceptions have moved to the new namespace `Mikehins\Cart\Exceptions`.

### Facade
The facade is now `Mikehins\Cart\Facades\CartFacade`.
If you are aliasing `Cart` in `config/app.php` (which is often automatic in recent Laravel), ensure it points to the new facade or remove manual registration.

### Events
Events dispatched by the cart (e.g., `cart.created`, `cart.adding`) function identically but are dispatched from the new codebase. Verify your listeners if you depend on specific event class instances.

## 6. Configuration Changes

If you published the configuration file, the structure remains largely the same, but you should republish it to ensure there are no legacy leftovers.

```bash
php artisan vendor:publish --provider="Mikehins\Cart\CartServiceProvider" --tag="config"
```

The config file is located at `config/shopping_cart.php` (default name may vary based on your local setup, commonly `shopping_cart.php` or `cart.php`).

## 7. Testing & Tooling Changes

- **Test Suite**: Migrated from PHPUnit to **Pest v4**.
- `Helpers\SessionMock` and `MockProduct` are now in `Mikehins\Cart\Tests\Helpers`.
- If your application tests extended this package's tests (rare), you must update them to Pest or adjust imports.

## 8. Behavioral Changes

- **Strict Types**: The package now uses `declare(strict_types=1)`. Passing incorrect types (e.g., string instead of int for quantity) that relied on loose casting may now throw `TypeError`.
- **Validation**: Internal validation is stricter. Ensure IDs are strings or integers, and required fields are present.

## 9. Recommended Upgrade Path

1.  [ ] **Backup** your application.
2.  [ ] **Remove** legacy package: `composer remove darryldecode/laravelshoppingcart`.
3.  [ ] **Update** server environment to PHP 8.3+ and Laravel 12.
4.  [ ] **Install** new package: `composer require mikehins/laravel-shoppingcart`.
5.  [ ] **Search & Replace** `Darryldecode\Cart` -> `Mikehins\Cart` in your `app/` directory.
6.  [ ] **Republish** config (optional but recommended).
7.  [ ] **Run Tests**: Execute your application test suite to catch type errors or namespace misses.

## 10. What Did Not Change

- **Public API**: The methods `add()`, `update()`, `remove()`, `get()`, `getContent()`, `total()`, `subTotal()` retain the same signatures and general logic.
- **Storage**: The session storage mechanism and data structure structure remain compatible (though mixing old and new serialized session data is not recommended).

## 11. Final Notes

This package is actively maintained by Mike Hins.
Please consult the [README.md](README.md) for full usage documentation.
