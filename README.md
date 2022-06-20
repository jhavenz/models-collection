# A simple way to collect/iterate over the eloquent models in a project

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
