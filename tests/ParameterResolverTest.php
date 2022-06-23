<?php

use Jhavenz\ModelsCollection\ModelsCollection;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\Post;

it('a filter gets applied when the collection is given a model class-string', function () {
    removeConfiguredDirectories();

    $models = ModelsCollection::create([Post::class]);

    $filters = new ReflectionProperty($models, 'filters');

    expect($filters->getValue($models)[0])->toBeInstanceOf(Closure::class);

    //$models = m(ModelsCollection::class, function (MockInterface $mock) {
    //    $mock->allows([
    //        'create' => ''
    //    ]);
    //});


});
