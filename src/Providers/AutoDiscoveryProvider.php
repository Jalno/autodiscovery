<?php
namespace Jalno\AutoDiscovery\Providers;

use Jalno\AutoDiscovery\{AutoDiscovery, PackageManifest};
use Illuminate\Support\ServiceProvider;

class AutoDiscoveryProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance("path.cache", $this->app->storagePath("cache"));
        $this->app->singleton(PackageManifest::class, function ($app) {
            return new PackageManifest($app->make("files"), $app->basePath(), $app->make("path.cache"));
        });
        $this->app->singleton(AutoDiscovery::class);
        $this->app->make(AutoDiscovery::class)->boot();
    }
}
