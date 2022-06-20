<?php

namespace Jhavens\IterativeEloquentModels;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IterativeEloquentModelsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('iterative-eloquent-models')
            ->hasConfigFile();
    }

    public function registeringPackage()
    {
        $this->app->singleton(ModelsCollection::class);
    }
}
