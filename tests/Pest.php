<?php

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Jhavenz\ModelsCollection\Repository;
use Jhavenz\ModelsCollection\Structs\Filesystem\DirectoryPath;
use Jhavenz\ModelsCollection\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function modelsPath(): DirectoryPath
{
    return tap(DirectoryPath::from(config('models-collection.models_path')),
        fn ($dir) => Repository::usingDirectories($dir)
    );
}

function schema(string $connection = 'testing'): Builder
{
    return Schema::connection($connection);
}
