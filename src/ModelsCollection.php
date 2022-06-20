<?php

namespace Jhavens\IterativeEloquentModels;

use ArrayIterator;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\ForwardsCalls;
use Innmind\Immutable\Set;
use IteratorAggregate;
use Jhavens\IterativeEloquentModels\Iterator\ModelIterator;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\DirectoryPath;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\FilePath;
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

    private Set $files;
    private Set $directories;
    private static array $filters = [];
    private static ?ModelIterator $iterator;

    /** enforce singleton */
    private function __construct() {}

    public function addFilter(Closure $filter): static
    {
        static::$filters[] = $filter;

        return $this;
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public static function toCollection(): Collection
    {
        return collect(static::make()->toArray())->map(fn (FilePath $filePath): Model => $filePath->instance());
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public static function toEloquentCollection(): EloquentCollection
    {
        return EloquentCollection::make(static::make()->toArray())->map(fn (FilePath $filePath): Model => $filePath->instance());
    }

    public function count(): int
    {
        return iterator_count($this->getIterator());
    }

    public static function flush(): static
    {
        self::$filters = [];
        self::$iterator = null;
        app()->forgetInstance(ModelsCollection::class);

        return static::make();
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
        return IterativeEloquentModels::directories();
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

    public static function make(): static
    {
        if (app()->resolved(ModelsCollection::class)) {
            return app(ModelsCollection::class);
        }

        return app()->instance(ModelsCollection::class, new static);
    }

    public function toArray(): array
    {
        return array_values(iterator_to_array($this->getIterator()));
    }

    public function __get(string $name)
    {
        return static::toCollection()->$name;
    }

    public function __call(string $method, array $parameters)
    {
        return $this->forwardDecoratedCallTo(self::toCollection(), $method, $parameters);
    }

    public static function __callStatic(string $method, array $parameters)
    {
        if (! method_exists($collection = self::toCollection(), $method)) {
            self::throwBadMethodCallException($method);
        }

        return $collection->$method(...$parameters);
    }
}
