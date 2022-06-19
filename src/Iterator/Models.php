<?php

namespace Jhavens\IterativeEloquentModels\Iterator;

use ArrayIterator;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
use Innmind\Immutable\Set;
use IteratorAggregate;
use Jhavens\IterativeEloquentModels\IterativeEloquentModels;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\DirectoryPath;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\FilePath;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as SymfonyFileInfo;

class Models implements IteratorAggregate, Arrayable, \Countable
{
    use Conditionable;

    private Set $directories;
    private static array $filters = [];
    private static ?ModelIterator $iterator;

    /** enforce singleton */
    private function __construct()
    {
        IterativeEloquentModels::setDefaultDirectory(
            config('app.models_path', config('iterative-eloquent-models.models_path'))
        );
    }

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
        app()->forgetIinstance(Models::class);

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
        return $this->directories ??= IterativeEloquentModels::directories();
    }

    private function getDirectoryFinders(): Set
    {
        return $this->getDirectories()->map(fn (string|DirectoryPath $path) => DirectoryPath::factory($path)->fileFinder());
    }

    private function getFilePaths(Finder $finder): array
    {
        return collect($finder)
            ->map(fn (SymfonyFileInfo $fileInfo) => FilePath::from($fileInfo->getRealPath()))
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
        if (app()->resolved(Models::class)) {
            return app(Models::class);
        }

        return app()->instance(Models::class, new static);
    }

    public function toArray(): array
    {
        return array_values($this->getIterator()->toArray());
    }
}
