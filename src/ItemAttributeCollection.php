<?php

declare(strict_types=1);

namespace Mikehins\Cart;

use Illuminate\Support\Collection;

final class ItemAttributeCollection extends Collection
{
    /**
     * @param  string|int  $key
     */
    #[\Override]
    public function __get(mixed $key): mixed
    {
        return $this->get($key);
    }
}
