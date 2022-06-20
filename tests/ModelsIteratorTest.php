<?php /** @noinspection PhpParamsInspection */

/** @noinspection PhpVoidFunctionResultUsedInspection */

use Illuminate\Database\Eloquent\Model;
use Jhavens\IterativeEloquentModels\IterativeEloquentModels;
use Jhavens\IterativeEloquentModels\ModelsCollection;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\DirectoryPath;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\FilePath;
use Jhavens\IterativeEloquentModels\Tests\Fixtures\Models\Pivot\RoleUser;
use Jhavens\IterativeEloquentModels\Tests\Fixtures\Models\Post;
use Jhavens\IterativeEloquentModels\Tests\Fixtures\Models\Role;
use Jhavens\IterativeEloquentModels\Tests\Fixtures\Models\User;

afterEach(fn () => ModelsCollection::flush());

it('isnt empty when not explicitly given a models directory', function () {
    expect($models = ModelsCollection::flush()->toArray())
        ->not
        ->toBeEmpty()
        ->and($models)
        ->toHaveCount(4);
});

it('can have a file set when given a directory string', function () {
    IterativeEloquentModels::usingDirectories(DirectoryPath::from(__DIR__.'/Fixtures/Models'));

    expect(ModelsCollection::make())
        ->toHaveCount(4)
        ->and(ModelsCollection::toCollection()->map(fn ($model) => $model::class)->sort()->values()->all())
        ->toMatchArray([
            RoleUser::class,
            Post::class,
            Role::class,
            User::class,
        ]);
});

it('acknowledges depth', function () {
    IterativeEloquentModels::usingDepth('== 0');

    $models = ModelsCollection::make();

    expect($models)
        ->toHaveCount(3)
        ->and(ModelsCollection::toCollection()->map(fn ($model) => $model::class)->sort()->values()->all())
        ->toMatchArray([
            Post::class,
            Role::class,
            User::class,
        ]);
});

it('can have a model class string filter', function () {
    IterativeEloquentModels::only(Post::class);

    expect($models = ModelsCollection::make()->toArray())->toHaveCount(1)
        ->and($file = $models[0])->toBeInstanceOf(FilePath::class)
        ->and($file->instance())->toBeInstanceOf(Post::class);
});

it('can have a model file string filter', function () {
    IterativeEloquentModels::only(FilePath::fromClassString(Post::class));

    expect($models = ModelsCollection::make()->toArray())->toHaveCount(1)
        ->and($file = $models[0])->toBeInstanceOf(FilePath::class)
        ->and($file->instance())->toBeInstanceOf(Post::class);
});

it('can have multiple model class string filters', function () {
    IterativeEloquentModels::only(Post::class, Role::class);

    $models = ModelsCollection::make()->toArray();

    expect($models)->toHaveCount(2)
        ->and($file = $models[1])->toBeInstanceOf(FilePath::class)
        ->and($file->instance())->toBeInstanceOf(Role::class);
});

it('can have multiple model file string filters', function () {
    IterativeEloquentModels::only(
        FilePath::factory(Post::class),
        FilePath::factory(Role::class)
    );

    expect($models = ModelsCollection::make()->toArray())->toHaveCount(2)
        ->and($file = $models[1])->toBeInstanceOf(FilePath::class)
        ->and($file->instance())->toBeInstanceOf(Role::class);
});

it('has all models when no filters are given', function () {
    $models = ModelsCollection::toCollection();

    expect($models)
        ->toHaveCount(4)
        ->and($models)->each->toBeInstanceOf(Model::class);
});

it('can have one model when filter is given', function () {
    IterativeEloquentModels::only(
        FilePath::factory(Post::class),
    );

    expect($models = ModelsCollection::toCollection())
        ->toHaveCount(1)
        ->and($models->first())->toBeInstanceOf(Model::class);
});

it('can have some models when filters are given', function () {
    IterativeEloquentModels::only(
        FilePath::factory(Post::class),
        FilePath::factory(User::class)
    );

    expect($models = ModelsCollection::toCollection())
        ->toHaveCount(2)
        ->and($models->map(fn ($model) => $model::class))
        ->each
        ->toBeIn([Post::class, User::class]);
});

it('forwards static method calls to the underlying collection', function () {
    expect(ModelsCollection::whereInstanceOf(Post::class)->first())->toBeInstanceOf(Post::class);
});


it('forwards non-static method calls to the underlying collection, allowing for higher order proxy', function () {
    expect(schema()->getAllTables())->toHaveCount(0);

    ModelsCollection::make()->each->runMigrations();

    expect(schema()->getAllTables())->toHaveCount(4);
});
