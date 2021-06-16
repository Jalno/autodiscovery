<?php
namespace Jalno\AutoDiscovery\Providers;

use Jalno\Lumen\Contracts;
use Jalno\AutoDiscovery\Repository;
use Illuminate\Support\ServiceProvider;

class AutoDiscoverProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $autoDiscover = new Repository(packages()->getPrimary());
        $this->app->instance(Contracts\IAutoDiscovery::class, $autoDiscover);

        $autoDiscover->register();
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
