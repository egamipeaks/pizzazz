<?php

namespace EgamiPeaks\Pizzazz;

use Illuminate\Support\Facades\Facade as LaravelFacade;

/**
 * @see \EgamiPeaks\Pizzazz\Pizzazz
 */
class Facade extends LaravelFacade
{
    protected static function getFacadeAccessor(): string
    {
        return \EgamiPeaks\Pizzazz\Pizzazz::class;
    }
}
