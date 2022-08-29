<?php

use Illuminate\Database\Eloquent\Model;
use Jhavenz\ModelsCollection\ModelsCollection;
use Jhavenz\PhpStructs\Filesystem\FilePath;
use Jhavenz\PhpStructs\Testing\Fixtures\Eloquent;

use function Jhavenz\ModelsCollection\modelsCollection;

it('has 1 model when a class-string filter is given')
    ->expect(fn () => ModelsCollection::usingFilters(Eloquent\Post::class))
    ->toHaveCount(1)
    ->first()
    ->toBeInstanceOf(Eloquent\Post::class);

it('has 1 model when a FilePath filter is given')
    ->expect(fn () => ModelsCollection::usingFilters(FilePath::factory(Eloquent\Post::class)))
    ->toHaveCount(1)
    ->first()
    ->toBeInstanceOf(Model::class);

it('has 2 models when 2 class-string filters are given')
    ->expect(fn () => ModelsCollection::usingFilters(Eloquent\Post::class, Eloquent\Role::class)->toClassString())
    ->toHaveCount(2)
    ->toMatchArray([
        Eloquent\Post::class,
        Eloquent\Role::class,
    ]);

it('has 2 models when 2 FilePath filters are given')
    ->expect(fn () => ModelsCollection::usingFilters(
        FilePath::factory(Eloquent\Post::class),
        FilePath::factory(Eloquent\Role::class)
    ))
    ->toHaveCount(2)
    ->map(fn ($item) => $item::class)
    ->toMatchArray([
        Eloquent\Post::class,
        Eloquent\Role::class,
    ]);

it('has all models when no filters are given')
    ->expect(fn () => modelsCollection())
    ->toHaveCount(5)
    ->each
    ->toBeInstanceOf(Model::class);
