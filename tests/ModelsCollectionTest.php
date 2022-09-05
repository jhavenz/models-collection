<?php
/** @noinspection PhpVoidFunctionResultUsedInspection */

use Jhavenz\ModelsCollection\ModelsCollection;
use Jhavenz\PhpStructs\Filesystem\DirectoryPath;
use Jhavenz\PhpStructs\Testing\Fixtures\Eloquent;

use function Jhavenz\ModelsCollection\removeConfiguredDirectories;

it('can have no directories or files given')
    ->expect(function () {
        removeConfiguredDirectories();

        return ModelsCollection::create();
    })
    ->toHaveCount(0);

it('isnt empty when not explicitly given any models, files, or directories')
    ->expect(fn () => ModelsCollection::create()->toArray())
    ->toBeArray()
    ->toHaveCount(5);

it('can have models from multiple directories', function () {
    $path = $this->getModelFixturesPath('Pivot/NestedModels/Permission.php');

    // @formatter:off
    config(['models-collection.directories' => [
        modelsPath(),
        otherModelsPath(),
    ]]);

    $directories = ModelsCollection::create()->toDirectories();

    $basePath = __DIR__.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR;

    expect($directories)->toMatchArray([
        $basePath.'Models',
        $basePath.'Models/Pivot',
        $basePath.'Models/Pivot/NestedModels',
        $basePath.'OtherModels',
        $basePath.'OtherModels/Pivot',
        $basePath.'OtherModels/Pivot/OtherNestedModels',
    ]);
});

it('has only has specified models when given 1 directory', function () {
    // @formatter:off
    config(['models-collection.directories' => [
        DirectoryPath::from(__DIR__.'/Fixtures/Models/Pivot')->path(),
    ]]);

    expect(ModelsCollection::create())
        ->toHaveCount(2)
        ->and(ModelsCollection::create()->toClassString()->values()->toArray())
        ->toMatchArray([
            Eloquent\Pivot\NestedModels\Permission::class,
            Eloquent\Pivot\RoleUser::class,
        ]);
});

it('can use the higher order collection proxy')
    ->expect(fn() => schema()->getAllTables())
    ->toHaveCount(0)
    ->and(function () {
        ModelsCollection::create()->each(fn (Eloquent\IHasMigration $model) => $model->runMigration());

        return schema()->getAllTables();
    })
    ->toHaveCount(5);

it('allows a model class-string to be given')
    ->expect(function () {
        removeConfiguredDirectories();

        return ModelsCollection::create([Eloquent\Post::class]);
    })
    ->toHaveCount(1)
    ->first()
    ->toBeInstanceOf(Eloquent\Post::class);

it('forwards static method calls to an illuminate collection')
    ->expect(fn() => ModelsCollection::whereInstanceOf(Eloquent\Post::class)->first())
    ->toBeInstanceOf(Eloquent\Post::class);

it('uses temporarily specified directories, then reverts back to the original configured directories')
    ->expect(fn() => ModelsCollection::create())
    ->toHaveCount(5)
    ->and(fn() => ModelsCollection::withoutConfiguredDirectories(DirectoryPath::from(__DIR__.'/Fixtures/Models/Pivot')))
    ->toHaveCount(2)
    ->and(fn() => ModelsCollection::create())
    ->toHaveCount(5);

// @formatter:off
it('uses temporarily specified directories in additional to configured directories, then reverts back to the original configured directories')
    ->expect(fn () => ModelsCollection::create())
    ->toHaveCount(5)
    ->and(fn () => ModelsCollection::withAdditionalDirectories(DirectoryPath::from(__DIR__.'/Fixtures/OtherModels')))
    ->toHaveCount(8)
    ->and(fn () => ModelsCollection::create())
    ->toHaveCount(5);
