<?php

namespace Haodai\UCenter\OAuth\Facades;

use Illuminate\Support\Facades\Facade;

class OAuthClient extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'oauth.client';
    }
}