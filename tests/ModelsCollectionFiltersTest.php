<?php

use Illuminate\Database\Eloquent\Model;
use Jhavenz\ModelsCollection\ModelsCollection;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\Post;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\Role;

it('has 1 model when a class-string filter is given')
    ->expect(fn() => ModelsCollection::usingFilters(Post::class))
    ->toHaveCount(1)
    ->first()
    ->toBeInstanceOf(Post::class);

it('has 1 model when a FilePath filter is given')
    ->expect(fn() => ModelsCollection::usingFilters(FilePath::factory(Post::class)))
    ->toHaveCount(1)
    ->first()
    ->toBeInstanceOf(Model::class);

it('has 2 models when 2 class-string filters are given')
    ->expect(fn() => ModelsCollection::usingFilters(Post::class, Role::class)->toClassString())
    ->toHaveCount(2)
    ->toMatchArray([
        Post::class,
        Role::class,
    ]);

it('has 2 models when 2 FilePath filters are given')
    ->expect(fn() => ModelsCollection::usingFilters(
        FilePath::factory(Post::class),
        FilePath::factory(Role::class)
    ))
    ->toHaveCount(2)
    ->map(fn($item) => $item::class)
    ->toMatchArray([
        Post::class,
        Role::class,
    ]);

it('has all models when no filters are given')
    ->expect(fn() => ModelsCollection::create()->toBase())
    ->toHaveCount(5)
    ->each
    ->toBeInstanceOf(Model::class);
