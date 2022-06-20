<?php /** @noinspection PhpParamsInspection */

/** @noinspection PhpVoidFunctionResultUsedInspection */

use Jhavenz\ModelsCollection\ModelsCollection;
use Jhavenz\ModelsCollection\Repository;
use Jhavenz\ModelsCollection\Structs\Filesystem\DirectoryPath;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\Pivot\RoleUser;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\Post;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\Role;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\User;

afterEach(fn () => ModelsCollection::flush());


it('isnt empty when not explicitly given a models directory', function () {
    expect($models = ModelsCollection::create()->toArray())
        ->not
        ->toBeEmpty()
        ->and($models)
        ->toHaveCount(4);
});

it('has all models when given a directory', function () {
    Repository::usingDirectories(DirectoryPath::from(__DIR__.'/Fixtures/Models'));

    expect(ModelsCollection::create())
        ->toHaveCount(4)
        ->and(ModelsCollection::create()->sort()->toClassString()->toArray())
        ->toMatchArray([
            Post::class,
            Role::class,
            RoleUser::class,
            User::class,
        ]);
});

it('acknowledges depth', function () {
    Repository::usingDepth('== 0');

    $models = ModelsCollection::create();

    expect($models)
        ->toHaveCount(3)
        ->and(ModelsCollection::toBase()->map(fn ($model) => $model::class)->sort()->values()->all())
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


it('forwards static method calls to the underlying collection', function () {
    expect(ModelsCollection::whereInstanceOf(Post::class)->first())->toBeInstanceOf(Post::class);
});

it('can have some models when filters are given', function () {
    Repository::only(
        FilePath::factory(Post::class),
        FilePath::factory(User::class)
    );

    expect($models = ModelsCollection::toBase())
        ->toHaveCount(2)
        ->and($models->map(fn ($model) => $model::class))
        ->each
        ->toBeIn([Post::class, User::class]);
});
