<?php

namespace Jhavenz\ModelsCollection;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Jhavenz\PhpStructs\Filesystem\DirectoryPath;
use Jhavenz\PhpStructs\Filesystem\FilePath;
use Jhavenz\PhpStructs\Filesystem\Path;
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
            return collect([
                'files' => fn () => $this->filterParams(
                    $params,
                    fn ($value) => ($fp = Path::factory($value)) instanceof FilePath ? $fp : null
                ),
                'directories' => fn () => $this->filterParams(
                    $params,
                    fn ($value) => ($dp = Path::factory($value)) instanceof DirectoryPath ? $dp : null
                ),
                'models' => fn () => $this->filterParams(
                    $params,
                    fn ($value) => $value instanceof Model ? $value : null
                ),
                'filters' => fn () => $this->filterParams(
                    $params,
                    fn ($value) => $value instanceof Closure ? $value : null
                ),
                'depth' => [],
            ])->map(function ($fallback, $param) use ($params) {
                return data_get($params, $param, $fallback);
            })->pipe(function (Collection $params) {
                return new ModelsCollection(...$params->all());
            });
        });
    }

    private function filterParams(array $params, Closure $fn): array
    {
        return array_reduce(
            Arr::flatten($params),
            function ($carry, $param) use ($fn) {
                if (!is_null($r = $fn($param))) {
                    $carry[] = $r;
                }

                return $carry;
            },
            []
        );
    }
}
