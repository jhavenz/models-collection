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
use Jhavenz\ModelsCollection\Exceptions\ModelCollectionException;
use Jhavenz\ModelsCollection\Structs\Filesystem\DirectoryPath;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;
use Jhavenz\ModelsCollection\Structs\Filesystem\Path;
use OutOfBoundsException;

use function collect;
use function Jhavenz\removeConfiguredDirectories;
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
        /** iterable<Model|class-string> */
        protected iterable $files = [],
        protected iterable $directories = [],
        protected iterable $filters = [],
        protected int|string|array $depth = []
    ) {
        foreach ($this->files as $i => $file) {
            $path = Path::factory($file);

            if (is_a($path->toClassString(), Model::class, true) && ! is_object($file)) {
                $this->files[$i] = $path;
            }
        }

        $this->directories = array_unique([
            ...config('models-collection.directories', []),
            ...collect($directories)->all()
        ]);
    }

    public function addFilters(string|Closure|FilePath ...$filters): static
    {
        $isModel = function (string $path) {
            $fp = Path::factory($path);

            return $fp instanceof FilePath &&
                   is_a($fp->classString(), Model::class, true);
        };

        $this->filters = [
            ...$this->filters,
            ...array_map(function (string|Closure|FilePath $filter) use ($isModel) {
                return match(true) {
                    $filter instanceof Closure => $filter,
                    $filter instanceof FilePath => fn (FilePath $filePath) => $filePath->path() === $filter->path(),
                    $isModel($filter) => fn (FilePath $filePath) => $filePath->classString() === FilePath::factory($filter)->classString(),
                    default => throw ModelCollectionException::invalidFilter($filter)
                };
            }, $filters)
        ];

        return $this;
    }

    public function all(bool $returningModels = false): iterable
    {
        return $this->apply($returningModels)->files;
    }

    /** applies logic on dirs, filters, etc. then returns a new instance containing the file paths  */
    public function apply(bool $returningModels = false): static
    {
        $items = collect($this->toArray())->when($returningModels, fn (Collection $files) => $files->map(fn (FilePath $fp) => $fp->instance()));

        dd($items);
        return static::create($items);
    }

    public static function create(iterable $files = [], iterable $directories = [], iterable $filters = [], int|string|array $depth = []): static
    {
        return app(ModelsCollection::class, compact('files', 'directories', 'filters', 'depth'));
    }

    public function count(): int
    {
        return count($this->toArray());
    }

    public function getIterator(): ModelPathIterator
    {
        return new ModelPathIterator(
            $this->getFiles()->getIterator(),
            $this->filters
        );
    }

    private function getDirectoryFiles(): Collection
    {
        return collect([
                ...config('models-collection.directories', []),
                ...$this->directories ?? []
            ])
            ->unique()
            ->flatMap(fn (string|DirectoryPath $directoryPath) => DirectoryPath::factory($directoryPath)
                ->setDepth($this->depth)
                ->getFiles()
            );
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

    public static function usingDepth(int|string|array $depth): static
    {
        return static::create(
            static::create(depth: $depth)->all()
        );
    }

    public static function usingDirectories(bool $withConfigDirs = true, string|DirectoryPath ...$directories): static
    {
        return with(config('models-collection.directories', []), function ($configuredDirs) use ($directories, $withConfigDirs) {
            $withConfigDirs || removeConfiguredDirectories();

            return tap(static::create(directories: $directories),
                fn () => config(['models-collection.directories' => $configuredDirs])
            );
        });
    }

    /**
     * @template TFilter (Closure(\Jhavenz\ModelsCollection\Structs\Filesystem\FilePath $filepath): bool)|class-string<Model>
     *
     * @param TFilter ...$filters
     */
    public static function usingFilters(string|Closure|FilePath ...$filters): static
    {
        return static::create()->addFilters(...func_get_args())->apply();
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
        return $this->forwardDecoratedCallTo($this->toBase(), $method, $parameters);
    }

    public static function __callStatic(string $method, array $parameters)
    {
        $self = static::create();

        if (method_exists($self, $method)) {
            return $self->$method(...$parameters);
        }

        if (! method_exists($collection = $self->toBase(), $method)) {
            self::throwBadMethodCallException($method);
        }

        return $collection->$method(...$parameters);
    }

    public function __get(string $name)
    {
        try {
            return $this->toBase()->__get($name);
        } catch (Exception) {
            throw new OutOfBoundsException("[{$name}] is not a valid method on the underlying collection");
        }
    }
}
