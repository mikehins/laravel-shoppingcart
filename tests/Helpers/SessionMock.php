<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/12/2015
 * Time: 10:23 PM
 */

namespace Mikehins\Cart\Tests\Helpers;

final class SessionMock
{
    public $putCalls = [];

    private $session = [];

    public function has($key)
    {
        return isset($this->session[$key]);
    }

    public function get($key)
    {
        return (isset($this->session[$key])) ? $this->session[$key] : null;
    }

    public function put($key, $value)
    {
        $this->session[$key] = $value;
        $this->putCalls[$key] = true;
    }
}
