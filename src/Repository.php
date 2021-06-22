<?php
namespace Jalno\AutoDiscovery;

use Laravel\Lumen\Application;
use Jalno\Lumen\Contracts\{IAutoDiscovery, IPackages, IPackage};

/**
 * @phpstan-type ComposerFile array{
 *  "name": string,
 *  "version": string,
 *  "description"?: string,
 *  "extra"?: array{
 *      "jalno"?: array{
 *          "package"?: class-string<IPackage>|array<class-string<IPackage>>
 *      }
 *  }
 * }
 */
class Repository implements IAutoDiscovery
{
    protected Application $app;
    protected IPackages $packages;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->packages = $app->make(IPackages::class);
    }

    public function register(): void
    {
        if (!is_dir($this->app->basePath("vendor"))) {
            return;
        }
        $namespaceDirectories = scandir($this->app->basePath("vendor"));
        if ($namespaceDirectories === false) {
            throw new \RuntimeException("can not scandir directory: '" . $this->app->basePath("vendor") . "'");
        }
        foreach ($namespaceDirectories as $namespace) {
            if (in_array($namespace, ['.', '..']) or !is_dir($this->app->basePath("vendor/" . $namespace))) {
                continue;
            }
            $directories = scandir($this->app->basePath("vendor/" . $namespace));
            if ($directories === false) {
                throw new \RuntimeException("can not scandir directory: '" . $this->app->basePath("vendor/" . $namespace) . "'");
            }
            foreach ($directories as $packageName) {
                if (in_array($namespace, ['.', '..'])) {
                    continue;
                }
                $composer = $this->getComposerFrom($this->app->basePath("vendor/" . $namespace . "/" . $packageName . "/composer.json"));
                if (empty($composer)) {
                    continue;
                }
                $packages = $this->findJalnoPackageFrom($composer);
                foreach ($packages as $package) {
                    $this->packages->register($package);
                }
            }
        }
    }

    /**
     * @return ComposerFile|array{}
     */
    protected function getComposerFrom(string $path): array
    {
        if (is_file($path)) {
            $content = file_get_contents($path);
            if ($content === false) {
                throw new \RuntimeException("can not read composer file in: '{$path}'");
            }
            return json_decode($content, true);
        }
        return [];
    }

    /**
     * @param ComposerFile $composer
     * @return class-string<IPackage>[]
     */
    protected function findJalnoPackageFrom(array $composer): array
    {
        if (
            !isset($composer["extra"]["jalno"]) or
            !isset($composer["extra"]["jalno"]["package"]) or
            empty($composer["extra"]["jalno"]["package"])
        ) {
            return [];
        }
        return (array)$composer["extra"]["jalno"]["package"];
    }
}
