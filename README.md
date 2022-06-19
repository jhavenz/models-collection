
[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/support-ukraine.svg?t=1" />](https://supportukrainenow.org)

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
$totals = [];

/** @var \Illuminate\Database\Eloquent\Model $model */
foreach (eloquentModels() as $model) {
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
