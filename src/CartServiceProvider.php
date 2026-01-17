<?php

declare(strict_types=1);

namespace Mikehins\Cart;

use Illuminate\Support\ServiceProvider;
use Override;

final class CartServiceProvider extends ServiceProvider
{
    protected bool $defer = false;

    public function boot(): void
    {
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__.'/config/config.php' => config_path('shopping_cart.php'),
            ], 'config');
        }
    }

    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'shopping_cart');

        $this->app->singleton('cart', function (\Illuminate\Contracts\Foundation\Application $app): Cart {
            /** @var string|null $storageClass */
            $storageClass = config('shopping_cart.storage');
            /** @var string|null $eventsClass */
            $eventsClass = config('shopping_cart.events');

            $storage = $storageClass ? new $storageClass : $app['session'];
            $events = $eventsClass ? new $eventsClass : $app['events'];
            $instanceName = 'cart';

            $session_key = '4yTlTDKu3oJOfzD';

            return new Cart(
                $storage,
                $events,
                $instanceName,
                $session_key,
                (array) config('shopping_cart')
            );
        });
    }

    #[Override]
    public function provides(): array
    {
        return [];
    }
}
