<?php

namespace Jhavenz\ModelsCollection;

use Closure;
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
            ->name('models-collection')
            ->hasConfigFile();
    }

    public function registeringPackage()
    {
        $this->app->bind(ModelsCollection::class, function (Application $app, array $params = []) {
            return new ModelsCollection(
                data_get($params, 'files', array_filter($flattened = Arr::flatten($params), fn ($f) => Path::factory($f) instanceof FilePath)),
                data_get($params, 'directories', array_filter($flattened, fn ($d) => Path::factory($d) instanceof DirectoryPath)),
                data_get($params, 'filters', array_filter($flattened, fn ($c) => $c instanceof Closure)),
            );
        });
    }
}
