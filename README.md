# A simple way to collect/iterate over the eloquent models in a project


This package will scan any directory that your Eloquent models are in (using Symfony's [Finder Component](https://symfony.com/doc/current/components/finder.html)) then create 
a collection (or iterable object) that you can then use to traverse all the models in your app.

---

- Use `models()` to get an instance of `\Illuminate\Support\Collection<array-key, Model>`

    _this is an alias for `ModelsCollection::toBase()`_
  

- Use `eloquentModels()` to get an instance of `\Illuminate\Database\Eloquent\Collection<array-key, Model>`

    _this is an alias for `ModelsCollection::make()`_


- `Jhavenz\ModelsCollection\ModelsCollection` has its own implementations of a few collection methods, though most calls to the collection classes above will be passed through the underlying collection
Example:
```php
# works as you'd expect - more examples given below, and in the tests
eloquentModels()->whereInstanceOf(\App\Models\User::class)->first() 
```

- You can tell the ModelsCollection to 'only' fetch specific models if you declare this prior to calling one of the methods listed above.
Example:
```php
# Will only contain these models the next time you call, e.g. models(), eloquentModels(), etc.
Jhavenz\ModelsCollection\ModelsCollection::only(
    \App\Models\Role::class, 
    \App\Models\Permission::class, 
    \App\Models\Pivot\RoleUser::class,
);
````   
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

**Optional**
can use `config('app.models_path')` if you'd rather not publish this little config file
```php
return [
    'models_path' => app_path('Models')
];
```

## Usage

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
      [etc...]
   ]
 */

// or

ModelsCollection::toBase()->map->getTable();

dump($totals);
/**
   [
      0 => 'users',
      1 => 'posts',
      [etc...]
   ]
 */
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
