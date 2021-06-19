<?php
namespace Jalno\AutoDiscovery;

use Laravel\Lumen\Application;
use Jalno\Lumen\Contracts;

/**
 * @phpstan-type ComposerFile array{
 *  "name": string,
 *  "version": string,
 *  "description"?: string,
 *  "extra"?: array{
 *      "jalno"?: array<string, class-string<Contracts\IPackage>|class-string<Contracts\IPackage>[]>
 *  }
 * }
 */
class Repository implements Contracts\IAutoDiscovery
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
        /** @var string[] $namespaceDirectories */
        $namespaceDirectories = scandir($this->app->basePath("vendor"));
        foreach ($namespaceDirectories as $namespace) {
            if (in_array($namespace, ['.', '..']) or !is_dir($this->app->basePath("vendor/" . $namespace))) {
                continue;
            }
            /** @var string[] $directories */
            $directories = scandir($this->app->basePath("vendor/" . $namespace));
            foreach ($directories as $packageName) {
                if (in_array($namespace, ['.', '..'])) {
                    continue;
                }
                $composer = $this->getComposerFrom($this->app->basePath("vendor/" . $namespace . "/" . $packageName . "/composer.json"));
                if (empty($composer)) {
                    continue;
                }
                /** @var ComposerFile $composer */
                $packages = $this->findJalnoPackageFrom($composer);
                foreach ($packages as $package) {
                    packages()->register($package);
                }
            }
        }
    }

    /**
     * @return ComposerFile|array<null>
     */
    private function getComposerFrom(string $path): array
    {
        if (is_file($path)) {
            /** @var string $content */
            $content = file_get_contents($path);
            return json_decode($content, true);
        }
        return [];
    }

    /**
     * @param ComposerFile $composer
	 * @return class-string<Contracts\IPackage>[]
	 */
    private function findJalnoPackageFrom(array $composer): array
    {
        if (
            !isset($composer["extra"]["jalno"]) or
            !isset($composer["extra"]["jalno"]["package"]) or
            empty($composer["extra"]["jalno"]["package"])
        ) {
            return [];
        }

        return is_array($composer["extra"]["jalno"]["package"]) ? $composer["extra"]["jalno"]["package"] : [$composer["extra"]["jalno"]["package"]];
    }
}
