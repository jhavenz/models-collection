<?php

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Jhavenz\ModelsCollection\Tests\TestCase;
use Jhavenz\PhpStructs\Filesystem\DirectoryPath;

uses(TestCase::class)->in(__DIR__);

function schema(string $connection = 'testing'): Builder
{
    return Schema::connection($connection);
}

function modelsPath(): DirectoryPath
{
    return DirectoryPath::from(__DIR__.'/Fixtures/Models');
}

function otherModelsPath(): DirectoryPath
{
    return DirectoryPath::from(__DIR__.'/Fixtures/OtherModels');
}
