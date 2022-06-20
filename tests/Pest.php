<?php

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Jhavens\IterativeEloquentModels\IterativeEloquentModels;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\DirectoryPath;
use Jhavens\IterativeEloquentModels\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function modelsPath(): DirectoryPath
{
    return tap(DirectoryPath::from(config('models-collection.models_path')),
        fn ($dir) => IterativeEloquentModels::usingDirectories($dir)
    );
}

function schema(string $connection = 'testing'): Builder
{
    return Schema::connection($connection);
}
