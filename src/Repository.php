<?php
namespace Jalno\AutoDiscovery;

use Laravel\Lumen\Application;
use Jalno\Lumen\Contracts;

/**
 * @phpstan-type ComposerFile array{
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
                /** @var ComposerFile $composer */
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

    /**
     * @return ComposerFile|array<null>
     */
    private function getComposerFrom(string $path): array
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
     * @return class-string<Contracts\IPackage>[]
     */
    private function findJalnoPackageFrom(array $composer): array
    {
        $jalnoExtra = $composer["extra"]["jalno"] ?? null;
        if (!isset($jalnoExtra["package"]) or empty($jalnoExtra["package"])) {
            return [];
        }

        return is_array($jalnoExtra["package"]) ? $jalnoExtra["package"] : [$jalnoExtra["package"]];
    }
}
