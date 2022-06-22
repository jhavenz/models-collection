<?php

namespace Jhavenz\ModelsCollection;

use Closure;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\ForwardsCalls;
use IteratorAggregate;
use Jhavenz\ModelsCollection\Iterator\ModelIterator;
use Jhavenz\ModelsCollection\Structs\Filesystem\DirectoryPath;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;
use Jhavenz\ModelsCollection\Structs\Filesystem\Path;
use OutOfBoundsException;
use function collect;
use function Jhavenz\rescueQuietly;

/**
 * @implements Enumerable
 * @mixin Collection
 */
class ModelsCollection implements IteratorAggregate, Arrayable, \Countable
{
    use Conditionable
        , ForwardsCalls;

    public function __construct(
        protected $files = [],
        protected $directories = [],
        protected $filters = []
    ) {
        $this->directories = array_unique([
            ...config('app.models_path', config('models-collection.directories', [])),
            ...$directories
        ]);
    }

    public function addFilter(Closure ...$filters): static
    {
        $this->filters = array_merge($this->filters, $filters);

        return $this;
    }

    public function all(): array
    {
        return $this->toArray();
    }

    public static function create(array $files = [], array $directories = [], array $filters = []): static
    {
        return app(ModelsCollection::class, compact('files', 'directories', 'filters'));
    }

    public function count(): int
    {
        return count($this->toArray());
    }

    public function getIterator(): ModelIterator
    {
        return new ModelIterator(
            $this->getFiles()->getIterator(),
            $this->filters
        );
    }

    private function getDirectoryFiles(): Collection
    {
        return collect([
                ...config('app.models_path', config('models-collection.directories', [])),
                ...$this->directories ?? []
            ])
            ->unique()
            ->flatMap(fn (string|DirectoryPath $directoryPath) => DirectoryPath::factory($directoryPath)->getFiles());
    }

    /** @return Collection<FilePath> */
    private function getFiles(): Collection
    {
        return collect($this->files ?? [])
            ->map(fn (string|FilePath $path) => FilePath::factory($path))
            ->merge($this->getDirectoryFiles())
            ->unique(fn (FilePath $fp) => $fp->path());
    }

    public function hasPath(mixed $path): bool
    {
        return $this->contains(fn (mixed $item) => Path::factory($item)->path() === Path::factory($path)->path());
    }

    public static function isValid(mixed $class): bool
    {
        return $class instanceof Model || (
            is_string($class)
            && class_exists($class)
            && is_a($class, Model::class, true)
        );
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public static function make(): EloquentCollection
    {
        return EloquentCollection::make(static::create()->toArray())->map(fn (FilePath $filePath): Model => $filePath->instance());
    }

    public function setDirectories(array $directories): static
    {
        $this->directories = $directories;

        return $this;
    }

    public function sort(bool $preserveKeys = false): static
    {
        return $this
            ->toBase()
            ->pipe(function (Collection $models) use ($preserveKeys) {
                $items = $models->all();

                uasort($items, function ($a, $b) {
                    return strnatcasecmp(
                        transform(self::toModel($a), fn (Model $model) => class_basename($model), fn () => PHP_INT_MIN),
                        transform(self::toModel($b), fn (Model $model) => class_basename($model), fn () => PHP_INT_MIN)
                    );
                });

                return $preserveKeys
                    ? static::create($items)
                    : static::create(array_values($items));
            });
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public function toBase(): Collection
    {
        return $this->getFiles()->map(fn (mixed $item): ?Model => self::toModel($item))->filter();
    }

    /** @return Collection<array-key, class-string<Model>> */
    public function toClassString(): Collection
    {
        return $this->map(fn ($item) => Path::factory($item)->toClassString());
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    private static function toModel(mixed $model): ?Model
    {
        return rescueQuietly(
            fn () => match(TRUE) {
                $model instanceof Model => $model,
                is_string($model), $model instanceOf Path => FilePath::factory($model)->instance(),
                default => null
            }
        );
    }

    public function __call(string $method, array $parameters)
    {
        return $this->forwardDecoratedCallTo(self::toBase(), $method, $parameters);
    }

    public static function __callStatic(string $method, array $parameters)
    {
        if (method_exists(self::class, $method)) {
            return static::create()->$method(...$parameters);
        }

        if (! method_exists($collection = self::toBase(), $method)) {
            self::throwBadMethodCallException($method);
        }

        return $collection->$method(...$parameters);
    }

    public function __get(string $name)
    {
        try {
            return static::toBase()->__get($name);
        } catch (Exception) {
            throw new OutOfBoundsException("[{$name}] is not a valid method on the underlying collection");
        }
    }
}
