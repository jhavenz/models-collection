<?php

namespace Jhavens\IterativeEloquentModels;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IterativeEloquentModelsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('models-collection')
            ->hasConfigFile();
    }

    public function registeringPackage()
    {
        $this->app->bind(ModelsCollection::class);
    }
}
