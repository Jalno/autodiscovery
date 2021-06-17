<?php
namespace Jalno\AutoDiscovery\Providers;

use Jalno\Lumen\Contracts;
use Jalno\AutoDiscovery\Repository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class AutoDiscoveryProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Contracts\IAutoDiscovery::class, Repository::class);
    }

    public function provides()
    {
        return [Contracts\IAutoDiscovery::class];
    }
}
