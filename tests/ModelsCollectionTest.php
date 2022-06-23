<?php /** @noinspection PhpParamsInspection */

/** @noinspection PhpVoidFunctionResultUsedInspection */

use Jhavenz\ModelsCollection\ModelsCollection;
use Jhavenz\ModelsCollection\Structs\Filesystem\DirectoryPath;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\Pivot\RoleUser;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\Post;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\Role;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\User;

it('can have no directories or files given', function () {
    config(['models-collection.directories' => []]);

    expect(ModelsCollection::create()->toArray())->toHaveCount(0);
});

it('isnt empty when not explicitly given any models, files, or directories', function () {
    expect($models = ModelsCollection::create()->toArray())
        ->toBeArray()
        ->and($models)
        ->toHaveCount(4);
});

it('has only has specified models when given 1 directory', function () {
    config(['models-collection.directories' => [
        DirectoryPath::from(__DIR__.'/Fixtures/Models/Pivot')->path()
    ]]);

    expect(ModelsCollection::create())
        ->toHaveCount(1)
        ->and(ModelsCollection::create()->toClassString()->values()->toArray())
        ->toMatchArray([
            RoleUser::class,
        ]);
});

it('acknowledges depth', function () {
    expect($models = ModelsCollection::usingDepth('== 0')->values())
        ->toHaveCount(3)
        ->and($models->toBase()->map(fn ($model) => $model::class)->sort()->values()->all())
        ->toMatchArray([
            Post::class,
            Role::class,
            User::class,
        ]);
});

it('forwards non-static method calls to the underlying collection, allowing higher order tap', function () {
    expect(schema()->getAllTables())->toHaveCount(0);

    ModelsCollection::create()->each->runMigrations();

    expect(schema()->getAllTables())->toHaveCount(4);
});

it('allows a model class-string to be given', function () {
    removeConfiguredDirectories();

    expect(ModelsCollection::create([Post::class])->first())->toBeInstanceOf(Post::class);
});

it('forwards static method calls to the underlying collection', function () {
    expect(ModelsCollection::whereInstanceOf(Post::class)->first())->toBeInstanceOf(Post::class);
});

it('can have some models when filters are given', function () {
    $models = ModelsCollection::usingFilters(
        FilePath::factory(Post::class),
        FilePath::factory(User::class)
    );

    dd($models);

    expect($models->toBase())
        ->toHaveCount(2)
        ->and($models->map(fn ($model) => $model::class))
        ->each
        ->toBeIn([Post::class, User::class]);
});
