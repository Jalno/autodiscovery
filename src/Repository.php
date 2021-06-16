<?php
namespace Jalno\AutoDiscovery;

use Jalno\Lumen\Contracts\{IAutoDiscover, IPackage};

class Repository implements IAutoDiscover
{
    protected IPackage $package;

    public function __construct(IPackage $package)
    {
        $this->package = $package;
    }

    public function register(): void
    {
        if (!is_dir($this->package->path("..", "vendor"))) {
            return;
        }
        foreach (scandir($this->package->path("..", "vendor")) as $namespace) {
            if (in_array($namespace, ['.', '..']) or !is_dir($this->package->path("..", "vendor", $namespace))) {
                continue;
            }
            foreach (scandir($this->package->path("..", "vendor", $namespace)) as $packageName) {
                if (in_array($namespace, ['.', '..'])) {
                    continue;
                }
                $composer = $this->getComposerFrom($this->package->path("..", "vendor", $namespace, $packageName, "composer.json"));
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
