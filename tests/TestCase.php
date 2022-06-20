<?php

namespace Jhavens\IterativeEloquentModels\Tests;

use Jhavens\IterativeEloquentModels\IterativeEloquentModelsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{

    protected function getPackageProviders($app): array
    {
        return [
            RayServiceProvider::class,
            IterativeEloquentModelsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        config()->set('models-collection.models_path', __DIR__.'/Fixtures/Models');
    }
}
