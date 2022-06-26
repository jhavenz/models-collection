## Models Collection

A simple way to iterate the Eloquent models in your project

_(see [tests](/tests) for additional examples)_

---

## Basic Usage:

```php
use App\Models\User;
use Jhavenz\ModelsCollection\ModelsCollection;

# works as you'd expect - more examples given below, and in the tests
eloquentModels()->whereInstanceOf(User::class)->first();

# functionally equivalent
ModelsCollection::usingFilters(User::class)->first(); 
```

You can also use multiple filters at once:

Example:

```php
use App\Models\User;
use App\Models\Post;
use App\OtherModels\Pivot\PostUser;
use Jhavenz\ModelsCollection\ModelsCollection;

# Will create a `ModelsCollection` with an instance if each model:
ModelsCollection::usingFilters(
    User::class, 
    Post::class, 
    PostUser::class,
);
````

*Note*:
The 'OtherModels' namespace (listed above) would need to be listed in your `models-collection.directories` configuration

Example:

```php
// config/models-collection.php

return [
    'directories' => [
        app_path('Models'),
        app_path('OtherModels')   
    ]
];
```

---

## Installation

You can install the package via composer:

```bash
composer require jhavenz/models-collection
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="models-collection-config"
```

This is the contents of the published config file:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Model Directories
    |--------------------------------------------------------------------------
    | 
    | Path(s) to where your models live in your project (use absolute paths).
    |
    | You're welcome to use as many directories as you'd like. This package
    | will automatically filter out files/folders that aren't Eloquent
    | models.
    |
    */
    
    'directories' => [
        app_path('Models'),
        //
    ],
];
```

## Usage

This package will scan any directory that your Eloquent models are in
(using Symfony's [Finder Component](https://symfony.com/doc/current/components/finder.html))
then return a `ModelsCollection` that can then be used as a Laravel collection, including some custom helper methods.

_examples given as if we have a `\App\Models\User` and `\App\Models\Post` model_

```php
use Jhavenz\ModelsCollection\ModelsCollection;

$totals = [];

/** @var \Illuminate\Database\Eloquent\Model $model */
foreach (ModelsCollection::create() as $model) {
    $totals[$model->getTable()] = $model->newQuery()->count();
}

dump($totals);
/**
   [
      'users' => 20,
      'posts' => 50,
   ]
 */
```

Any methods called, but not found on the `ModelsCollection` class, will automatically resolve the models relative to
any files, directories, and/or filters you've listed within your `ModelsCollection`, collect these models, then call
that method on an `\Illuminate\Support\Collection` before being returned to you.

```php
ModelsCollection::create()->map->getTable();

dump($totals);
/**
   [
      0 => 'users',
      1 => 'posts',
   ]
 */
```

### Depth Usage

Since this package uses the Symfony `Finder` component, we can apply depth filters when creating a new instance:

_this example assumes there's a `\App\Models\Pivot\PostUser` model in addition to the example models above_

Example:

```php
use App\Models\Pivot\PostUser;
use Jhavenz\ModelsCollection\ModelsCollection;

$pivotModels = ModelsCollection::usingDepth('== 1');

$pivotModels->count(); //=> 1
$pivotModels->first(); //=> \App\Models\Pivot\PostUser instance

//or, if we want to exclude this 'pivot' model
$nonPivotModels = ModelsCollection::usingDepth('< 1');

$nonPivotModels->count(); //=> 2
$nonPivotModels->whereInstanceOf(PostUser::class)->isEmpty(); //=> true
```

_see [symfony's docs](https://symfony.com/doc/current/components/finder.html#directory-depth) on this
for more depth control options_

### Filter Usage

In addition to the basic class string filters used above, this package also provides the ability to
filter using a custom callback.

Example:

```php
use App\Models\Pivot\PostUser;
use Jhavenz\ModelsCollection\ModelsCollection;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

// basic example...
$pivotModels = ModelsCollection::usingFilters(function (FilePath $filepath) {
    //$filepath is essentially a \SplFileInfo on steroids...
    return $filepath->instance() instanceof Pivot;
});

$pivotModels->count(); //=> 1
$pivotModels->first(); //=> instance of PostUser

//more advanced example
$belongsToPost = ModelsCollection::usingFilters(function (FilePath $filepath) {
    
    //e.g. find all models that 'BelongTo' to a 'user'...
    foreach ($filepath->reflectionClass()->getMethods(ReflectionMethod::IS_PUBLIC) as $rfxMethod) {
        $methodName = $rfxMethod->getName();
        $methodReturnType = $rfxMethod->getReturnType();
        
        if ($methodName === 'user' && $methodReturnType === BelongsTo::class) {
           return true;
        }
    }
    
    return false;
});

$belongsToPost->count(); //=> 2
$belongsToPost->all(); //=> [PostUser {#...}, Post {#...}]
```

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome!

## Security Vulnerabilities

Please email me at mail@jhavens.tech if any vulnerabilities are found

## Credits

- [Jonathan Havens](https://github.com/jhavenz)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
