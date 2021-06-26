<?php

namespace Jalno\AutoDiscovery;

use Illuminate\Contracts\Container\Container;

class AutoDiscovery
{
	protected Container $app;
	public function __construct(Container $app)
	{
		$this->app = $app;
	}

	public function boot(): void
	{
		$providers = $this->app->make(PackageManifest::class)->providers();
		
		foreach ($providers as $provider) {
			$this->app->register($provider);
        }
	}
}