<?php

use Jhavens\IterativeEloquentModels\IterativeEloquentModels;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\DirectoryPath;
use Jhavens\IterativeEloquentModels\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function modelsPath(): DirectoryPath
{
    return tap(DirectoryPath::from(config('iterative-eloquent-models.models_path')),
        fn ($dir) => IterativeEloquentModels::usingDirectories($dir)
    );
}
