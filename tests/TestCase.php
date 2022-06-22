<?php

namespace Jhavenz\ModelsCollection\Tests;

use Jhavenz\ModelsCollection\ModelsCollectionServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{

    protected function getPackageProviders($app): array
    {
        return [
			RayServiceProvider::class,
			ModelsCollectionServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        config()->set('models-collection.directories', [
            __DIR__.'/Fixtures/Models'
        ]);
    }
}
