<?php

namespace Haodai\UCenter\OAuth;

use Haodai\UCenter\OAuth\Api\OAuthApi;
use Illuminate\Support\ServiceProvider;

class OAuthServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('oauth.client', function (){
            return new OAuthClient(new OAuthApi(new OAuthGuzzle()));
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/oauth.php' => config_path('oauth.php')
        ]);
    }

    public function provides()
    {
        return ['oauth.client'];
    }
}