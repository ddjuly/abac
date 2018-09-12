<?php

namespace Abac;

use Illuminate\Support\Facades\Facade;

class AbacFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'abac';
    }
}
