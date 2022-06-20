<?php

use Illuminate\Database\Eloquent\Model;
use Jhavens\IterativeEloquentModels\IterativeEloquentModels;
use Jhavens\IterativeEloquentModels\ModelsCollection;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\FilePath;
use Jhavens\IterativeEloquentModels\Tests\Fixtures\Models\Post;
use Jhavens\IterativeEloquentModels\Tests\Fixtures\Models\Role;

beforeEach(fn () => ModelsCollection::flush());

it('can have a model class string filter', function () {
    IterativeEloquentModels::only(Post::class);

    expect($models = ModelsCollection::create()->toArray())->toHaveCount(1)
        ->and($file = $models[0])->toBeInstanceOf(FilePath::class)
        ->and($file->instance())->toBeInstanceOf(Post::class);
});

it('can have a model file string filter', function () {
    IterativeEloquentModels::only(FilePath::fromClassString(Post::class));

    expect($models = ModelsCollection::create()->toArray())->toHaveCount(1)
        ->and($file = $models[0])->toBeInstanceOf(FilePath::class)
        ->and($file->instance())->toBeInstanceOf(Post::class);
});

it('can have multiple model class string filters', function () {
    IterativeEloquentModels::only(Post::class, Role::class);

    $models = ModelsCollection::create()->toArray();

    expect($models)->toHaveCount(2)
        ->and($file = $models[1])->toBeInstanceOf(FilePath::class)
        ->and($file->instance())->toBeInstanceOf(Role::class);
});

it('can have multiple model file string filters', function () {
    IterativeEloquentModels::only(
        FilePath::factory(Post::class),
        FilePath::factory(Role::class)
    );

    expect($models = ModelsCollection::create()->toArray())->toHaveCount(2)
        ->and($file = $models[1])->toBeInstanceOf(FilePath::class)
        ->and($file->instance())->toBeInstanceOf(Role::class);
});

it('has all models when no filters are given', function () {

    $models = ModelsCollection::toBase();

    expect($models)
        ->toHaveCount(4)
        ->and($models)->each->toBeInstanceOf(Model::class);
});

it('can have one model when filter is given', function () {
    IterativeEloquentModels::only(
        FilePath::factory(Post::class),
    );

    expect($models = ModelsCollection::toBase())
        ->toHaveCount(1)
        ->and($models->first())->toBeInstanceOf(Model::class);
});
