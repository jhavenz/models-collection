<?php

namespace Jhavenz\ModelsCollection;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Jhavenz\ModelsCollection\Structs\Filesystem\DirectoryPath;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;
use Jhavenz\ModelsCollection\Structs\Filesystem\Path;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ModelsCollectionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name($name = 'models-collection')
            ->hasConfigFile($name);
    }

    public function registeringPackage()
    {
        $this->app->bind(ModelsCollection::class, function (Application $app, array $params = []) {
            // @formatter:off
            return new ModelsCollection(
                data_get($params, 'files', fn () => $this->filterParams($params, fn ($value) => ($fp = Path::factory($value)) instanceof FilePath ? $fp : null)),
                data_get($params, 'directories', fn () => $this->filterParams($params, fn ($value) => ($dp = Path::factory($value)) instanceof DirectoryPath ? $dp : null)),
                data_get($params, 'models', fn () => $this->filterParams($params, fn ($value) => $value instanceof Model ? $value : null)),
                data_get($params, 'filters', fn () => $this->filterParams($params, fn ($value) => $value instanceof Closure ? $value : null)),
                data_get($params, 'depth', []),
            );
        });
    }

    private function filterParams(array $params, Closure $fn): array
    {
        return array_reduce(
            Arr::flatten($params),
            function ($carry, $param) use ($fn) {
                if (! is_null($r = $fn($param))) {
                    $carry[] = $r;
                }

                return $carry;
            },
        []);
    }
}
