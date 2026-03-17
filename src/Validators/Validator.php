<?php

declare(strict_types=1);

namespace Mikehins\Cart\Validators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

abstract class Validator
{
    protected static ?Factory $factory = null;

    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = self::instance();

        return match (count($args)) {
            0 => $instance->$method(),
            1 => $instance->$method($args[0]),
            2 => $instance->$method($args[0], $args[1]),
            3 => $instance->$method($args[0], $args[1], $args[2]),
            4 => $instance->$method($args[0], $args[1], $args[2], $args[3]),
            default => call_user_func_array([$instance, $method], $args),
        };
    }

    final public static function instance(): Factory
    {
        if (! static::$factory instanceof Factory) {
            $loader = new FileLoader(
                new Filesystem, '/Translations'
            );

            $translator = new Translator($loader, 'en');
            static::$factory = new Factory($translator);
        }

        return static::$factory;
    }
}
