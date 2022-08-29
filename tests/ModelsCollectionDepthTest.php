<?php

use Jhavenz\ModelsCollection\ModelsCollection;
use Jhavenz\PhpStructs\Testing\Fixtures\Eloquent;
use Jhavenz\PhpStructs\Testing\Fixtures\OtherEloquentModels;

it('can use an exact depth of 0 with 1 directory')
    //top-level folder has 3 models in it
    ->expect(fn () => ModelsCollection::usingDepth('== 0')->toClassString())
    ->toHaveCount(3)
    ->toMatchArray([
        Eloquent\Post::class,
        Eloquent\Role::class,
        Eloquent\User::class,
    ]);

it('can use an exact depth of 0 with 2 directories')
    //top-level folder has 3 models in it
    ->expect(function () {
        return ModelsCollection::withAdditionalDirectories(function () {
            return ModelsCollection::usingDepth(['== 0'])->toClassString();
        }, otherModelsPath());
    })
    ->toHaveCount(4)
    ->toMatchArray([
        Eloquent\Post::class,
        Eloquent\Role::class,
        Eloquent\User::class,
        OtherEloquentModels\Tag::class,
    ]);

it('can use an exact depth of 1 with 1 directory')
    ->expect(fn () => ModelsCollection::usingDepth(['== 1'])->toClassString())
    ->toHaveCount(1)
    ->toMatchArray([
        Eloquent\Pivot\RoleUser::class,
    ]);

it('can use an exact depth of 1 with multiple directories')
    ->expect(function () {
        return ModelsCollection::withAdditionalDirectories(function () {
            return ModelsCollection::usingDepth(['== 1'])->toClassString();
        }, otherModelsPath());
    })
    ->toHaveCount(2)
    ->toMatchArray([
        Eloquent\Pivot\RoleUser::class,
        OtherEloquentModels\Pivot\TagUser::class,
    ]);

it('can use depth > 1 with 1 directory')
    ->expect(fn () => ModelsCollection::usingDepth(['> 1'])->toClassString())
    ->toHaveCount(1)
    ->toMatchArray([
        Eloquent\Pivot\NestedModels\Permission::class,
    ]);

it('can use depth > 1 with multiple directories')
    ->expect(fn () => ModelsCollection::withAdditionalDirectories(
        fn () => ModelsCollection::usingDepth(['> 1'])->toClassString(),
        otherModelsPath()
    ))
    ->toHaveCount(2)
    ->toMatchArray([
        Eloquent\Pivot\NestedModels\Permission::class,
        OtherEloquentModels\Pivot\OtherNestedModels\Thread::class,
    ]);
