<?php

use Illuminate\Database\Eloquent\Model;
use Jhavenz\ModelsCollection\EloquentFiles;
use Jhavenz\PhpStructs\Filesystem\Local\LocalFilePath;

it('creates a collection with all models', function () {
    $models = EloquentFiles::fromConfig();

    $models->toFiles()->each(function (LocalFilePath $path) {
        expect($path->isA(Model::class))->toBeTrue();
    });
});
