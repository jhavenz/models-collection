<?php

namespace Jhavenz\ModelsCollection\Tests;

use Jhavenz\ModelsCollection\ModelsCollectionServiceProvider;
use Jhavenz\PhpStructs;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelRay\RayServiceProvider;
use Symfony\Component\Finder\Finder;

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
            $this->getModelFixturesPath(),
            $this->getOtherModelFixturesPath(),
        ]);

        $app->booted(function () use ($app) {
            $dir = $app['config']->get('models-collection.directories');

            spl_autoload_register(function () use ($dir) {
                $files = Finder::create()->in($dir)->ignoreDotFiles(true)->files();

                foreach ($files as $file) {
                    try {
                        require_once $file->getRealPath();
                    } catch (\Throwable $e) {
                        continue;
                    }
                }
            });
        });
    }

    protected function getModelFixturesPath(?string $path = null): string
    {
        return PhpStructs\srcPath(
            sprintf(
                'Testing/Fixtures/Eloquent%s',
                $path ? str($path)->start(DIRECTORY_SEPARATOR)->toString() : ''
            )
        );
    }

    protected function getOtherModelFixturesPath(?string $path = null): string
    {
        return PhpStructs\srcPath(
            sprintf(
                'Testing/Fixtures/OtherEloquentModels%s',
                $path ? str($path)->start(DIRECTORY_SEPARATOR)->toString() : ''
            )
        );
    }
}
