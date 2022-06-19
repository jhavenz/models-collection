<?php

use Illuminate\Database\Eloquent\Model;
use Jhavens\IterativeEloquentModels\IterativeEloquentModels;
use Jhavens\IterativeEloquentModels\Iterator\Models;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\DirectoryPath;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\FilePath;
use Jhavens\IterativeEloquentModels\Tests\Fixtures\Models\Pivot\RoleUser;
use Jhavens\IterativeEloquentModels\Tests\Fixtures\Models\Post;
use Jhavens\IterativeEloquentModels\Tests\Fixtures\Models\Role;
use Jhavens\IterativeEloquentModels\Tests\Fixtures\Models\User;

afterEach(fn () => Models::flush());

it('can have an empty files set', function () {
    expect(iterator_to_array(Models::make()))->toBeEmpty();
});

it('can have a file set when given a directory string', function () {
    IterativeEloquentModels::usingDirectories($path = DirectoryPath::from(__DIR__.'/Fixtures'));

    $models = Models::make();

    expect($models)
        ->toHaveCount(4)
        ->and(Models::toCollection()->map(fn ($model) => $model::class)->all())
        ->toMatchArray([
            Post::class,
            User::class,
            Role::class,
            RoleUser::class
        ]);
});

it('acknowledges depth', function () {
    IterativeEloquentModels::usingDepth('< 1');

    $models = Models::make();

    expect($models)
        ->toHaveCount(3)
        ->and(Models::toCollection()->map(fn ($model) => $model::class)->all())
        ->toMatchArray([
            Post::class,
            User::class,
            Role::class
        ]);
});

it('can have a model class string filter', function () {
    IterativeEloquentModels::only(Post::class);

    expect($models = Models::make()->toArray())->toHaveCount(1)
        ->and($file = $models[0])->toBeInstanceOf(FilePath::class)
        ->and($file->instance())->toBeInstanceOf(Post::class);
});

it('can have a model file string filter', function () {
    IterativeEloquentModels::only(FilePath::fromClassString(Post::class));

    expect($models = Models::make()->toArray())->toHaveCount(1)
        ->and($file = $models[0])->toBeInstanceOf(FilePath::class)
        ->and($file->instance())->toBeInstanceOf(Post::class);
});

it('can have multiple model class string filters', function () {
    IterativeEloquentModels::only(Post::class, Role::class);

    $models = Models::make()->toArray();

    expect($models)->toHaveCount(2)
        ->and($file = $models[1])->toBeInstanceOf(FilePath::class)
        ->and($file->instance())->toBeInstanceOf(Role::class);
});

it('can have multiple model file string filters', function () {
    IterativeEloquentModels::only(
        FilePath::factory(Post::class),
        FilePath::factory(Role::class)
    );

    expect($models = Models::make()->toArray())->toHaveCount(2)
        ->and($file = $models[1])->toBeInstanceOf(FilePath::class)
        ->and($file->instance())->toBeInstanceOf(Role::class);
});

it('has all models when no filters are given', function () {
    $models = Models::toCollection();

    expect($models)
        ->toHaveCount(4)
        ->and($models)->each->toBeInstanceOf(Model::class);
});

it('can have one model when filter is given', function () {
    IterativeEloquentModels::only(
        FilePath::factory(Post::class),
    );

    expect($models = Models::toCollection())
        ->toHaveCount(1)
        ->and($models->first())->toBeInstanceOf(Model::class);
});

it('can have some models when filters are given', function () {
    IterativeEloquentModels::only(
        FilePath::factory(Post::class),
        FilePath::factory(User::class)
    );

    expect($models = Models::toCollection())
        ->toHaveCount(2)
        ->and($models->map(fn ($model) => $model::class))
        ->each
        ->toBeIn([Post::class, User::class]);
});
