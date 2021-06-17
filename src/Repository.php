<?php
namespace Jalno\AutoDiscovery;

use Laravel\Lumen\Application;
use Jalno\Lumen\Contracts\IAutoDiscovery;

class Repository implements IAutoDiscovery
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register(): void
    {
        if (!is_dir($this->app->basePath("vendor"))) {
            return;
        }
        foreach (scandir($this->app->basePath("vendor")) as $namespace) {
            if (in_array($namespace, ['.', '..']) or !is_dir($this->app->basePath("vendor/" . $namespace))) {
                continue;
            }
            foreach (scandir($this->app->basePath("vendor/" . $namespace)) as $packageName) {
                if (in_array($namespace, ['.', '..'])) {
                    continue;
                }
                $composer = $this->getComposerFrom($this->app->basePath("vendor/" . $namespace . "/" . $packageName . "/composer.json"));
                if (empty($composer)) {
                    continue;
                }
                $packages = $this->findJalnoPackageFrom($composer);
                foreach ($packages as $package) {
                    packages()->register($package);
                }
            }
        }
    }

    private function getComposerFrom(string $path): array
    {
        return is_file($path) ? json_decode(file_get_contents($path), true) : [];
    }

    private function findJalnoPackageFrom(array $composer): array
    {
        if (!isset($composer["extra"]["jalno"]["package"]) or empty($composer["extra"]["jalno"]["package"])) {
            return [];
        }

        return is_array($composer["extra"]["jalno"]["package"]) ? $composer["extra"]["jalno"]["package"] : [$composer["extra"]["jalno"]["package"]];
    }
}
