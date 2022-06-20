<?php

namespace Jhavenz\ModelsCollection;

use ArrayIterator;
use Closure;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\ForwardsCalls;
use Innmind\Immutable\Set;
use IteratorAggregate;
use Jhavenz\ModelsCollection\Iterator\ModelIterator;
use Jhavenz\ModelsCollection\Settings\Repository;
use Jhavenz\ModelsCollection\Structs\Filesystem\DirectoryPath;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;
use Jhavenz\ModelsCollection\Structs\Filesystem\Path;
use OutOfBoundsException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as SymfonyFileInfo;
use function app;
use function collect;

/**
 * @implements Enumerable
 * @mixin Collection
 */
class ModelsCollection implements IteratorAggregate, Arrayable, \Countable
{
    use Conditionable
        , ForwardsCalls;

    private static array $filters = [];
    private static ?ModelIterator $iterator;

    /** enforce singleton */
    private function __construct(protected $items = []) {}

    public function addFilter(Closure $filter): static
    {
        static::$filters[] = $filter;

        return $this;
    }

    public function all(): array
    {
        return $this->toArray();
    }

    public static function create($items = []): static
    {
        if (app()->resolved($class = ModelsCollection::class)) {
            return tap(app($class), fn (self $self) => count($items) && $self->setItems($items));
        }

        return app()->instance($class, new static($items));
    }

    public function count(): int
    {
        return count($this->toArray());
    }

    public static function flush(): void
    {
        Repository::flush();
        self::$filters = [];
        self::$iterator = null;
        unset(app()[ModelsCollection::class]);
    }

    public function getIterator(): ModelIterator
    {
        return self::$iterator ??= new ModelIterator(new ArrayIterator(
            $this->getFiles()->toList()
        ), self::$filters);
    }

    /** @return Set<DirectoryPath> */
    public function getDirectories(): Set
    {
        return Repository::directories();
    }

    private function getDirectoryFinders(): Set
    {
        return $this->getDirectories()->map(fn (string $path) => DirectoryPath::factory($path)->fileFinder());
    }

    private function getFilePaths(Finder $finder): array
    {
        return collect($finder)
            ->map(fn (SymfonyFileInfo $fileInfo) => $fileInfo->isFile() ? FilePath::from($fileInfo->getRealPath()) : null)
            ->filter()
            ->all();
    }

    /** @return Set<FilePath> */
    private function getFiles(): Set
    {
        return $this
            ->getDirectoryFinders()
            ->flatMap(function (Finder $finder) {
                return Set::objects(...$this->getFilePaths($finder));
            });
    }

    public function getFilterCount(): int
    {
        return count(static::$filters ?? []);
    }

    public function hasPath(string|SplFileInfo|FilePath $path): bool
    {
        return self::$iterator->contains($path);
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

    public function setItems(array $items): static
    {
        $this->items = $items;

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
        return empty($this->items)
            ? array_values(iterator_to_array($this->getIterator()))
            : $this->items;
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public static function toBase(): Collection
    {
        return collect(static::create()->toArray())->map(fn (Model|string|FilePath $item): Model => self::toModel($item));
    }

    /** @return Collection<array-key, class-string<Model>> */
    public function toClassString(): Collection
    {
        return $this
            ->map(fn ($item) =>
                ($class = self::toModel($item))
                    ? get_class($class)
                    : null
            )
            ->filter();
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    private static function toModel(Model|string|FilePath|null $model): ?Model
    {
        return match(TRUE) {
            $model instanceof Model => $model,
            is_string($model), $model instanceOf Path => FilePath::factory($model)->instance(),
            default => null
        };
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
