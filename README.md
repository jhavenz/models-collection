# provides a simple way to iterate over eloquent model instances

## Installation

You can install the package via composer:

```bash
composer require jhavens/iterative-eloquent-models
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="iterative-eloquent-models-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
use Jhavens\IterativeEloquentModels\Iterator\Models;

$totals = [];

/** @var \Illuminate\Database\Eloquent\Model $model */
foreach (Models::make() as $model) {
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

Models::toCollection()->map->getTable();

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

Please email me at mail@jhavens.tech if if/when any vulnerabilities are found

## Credits

- [Jonathan Havens](https://github.com/jhavens)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
