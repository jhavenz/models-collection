<?php

namespace Jhavenz\ModelsCollection;

use ArrayIterator;
use Closure;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\ForwardsCalls;
use IteratorAggregate;
use Jhavenz\ModelsCollection\Exceptions\ModelCollectionException;
use Jhavenz\PhpStructs\Filesystem\Local\Collections\Directories;
use Jhavenz\PhpStructs\Filesystem\Local\Collections\Files;
use Jhavenz\PhpStructs\Filesystem\Path;
use Jhavenz\PhpStructs\Filesystem\Shared\DirectoryPath;
use Jhavenz\PhpStructs\Filesystem\Shared\FilePath;
use OutOfBoundsException;

use function collect;

/**
 * @implements Enumerable
 * @mixin Collection
 */
class ModelsCollection implements IteratorAggregate, Arrayable, \Countable
{
    use Conditionable
        , ForwardsCalls;

    protected Files $files;
    protected Directories $directories;
    protected Collection $models;

    public function __construct(
        ?iterable $files = null,
        ?iterable $directories = null,
        ?iterable $models = null,
        protected ?iterable $filters = [],
        protected int|string|array $depth = []
    ) {
        $this->files = Files::make($files ?? [])
            ->filter(fn (FilePath $filePath) => is_a($filePath->toClassString(), Model::class, true));

        $this->directories = Directories::make([
            ...config('models-collection.directories', []),
            ...collect($directories ?? [])->all(),
        ]);

        $this->models = Collection::make($models ?? [])->filter(fn ($item) => $item instanceof Model);
    }

    public function addFilters(string|Closure|FilePath ...$filters): static
    {
        foreach (self::toModels($filters) as $model) {
            if ($model && !$this->hasModel($model)) {
                $this->models->add(FilePath::fromClassString($model::class)->instance());
            }
        }

        $this->filters = [
            ...$this->filters,
            ...array_map(function (string|callable|FilePath $filter) {
                if (is_callable($filter)) {
                    return $filter;
                }

                $filter = Path::from($filter);

                return function (FilePath $filePath) use ($filter) {
                    return $filter instanceof FilePath && $filePath->isA($filter->toClassString());
                };
            }, $filters),
        ];

        return $this;
    }

    public function all(): array
    {
        return $this->models->all();
    }

    /** applies logic on dirs, filters, etc. then returns a new instance containing the file paths  */
    public function apply(): static
    {
        /** @var FilePath $filePath */
        foreach ($this->getIterator() as $filePath) {
            if ($this->hasModel($model = self::toModel($filePath))) {
                continue;
            }

            if (!$this->passesFilters($filePath)) {
                $this->models = $this->models->filter(fn (Model $m) => $filePath->classString() !== $m::class);
                continue;
            }

            $this->models->add($model);
        }

        return $this;
    }

    public static function create(
        iterable $files = [],
        iterable $directories = [],
        iterable $models = [],
        iterable $filters = [],
        int|string|array $depth = []
    ): static {
        return app(ModelsCollection::class, compact('files', 'directories', 'models', 'filters', 'depth'));
    }

    public function count(): int
    {
        return count($this->toArray());
    }

    public function getIterator(): ArrayIterator
    {
        return $this->getFiles()->getIterator();
    }

    /** @return Collection<FilePath> */
    private function getFiles(): Collection
    {
        $data = collect(
            $this
                ->files
                ->merge($this->directories->toFiles($this->depth))
                ->map(fn (Path $path) => $path)
        );

        return $data
            ->unique(fn (FilePath $fp) => $fp->path())
            ->filter(fn (FilePath $fp) => rescueQuietly(
                fn () => $fp->isA(Model::class) && $this->passesFilters($fp),
                fn () => false
            ))
            ->dd(__METHOD__);
    }

    public function hasFilePath(mixed $path): bool
    {
        return $this->files->contains(
            fn (FilePath $fp) => $fp->path() === Path::from($path)->path()
        );
    }

    public static function make(
        iterable $files = [],
        iterable $directories = [],
        iterable $models = [],
        iterable $filters = [],
        int|string|array $depth = []
    ): Collection {
        return static::create(...func_get_args())->toBase();
    }

    public static function usingDepth(int|string|array $depth): static
    {
        return static::create(depth: $depth);
    }

    private static function usingDirectories(
        ?Closure $func = null,
        bool $withConfigDirs = true,
        string|DirectoryPath ...$directories
    ): mixed {
        return with(
            config()->get('models-collection.directories', []),
            function ($configuredDirs) use ($func, $directories, $withConfigDirs) {
                config()->set(
                    'models-collection.directories',
                    collect($directories)
                        ->when($withConfigDirs, fn (Collection $dirs) => $dirs->merge($configuredDirs))
                        ->map(fn ($dir) => DirectoryPath::from($dir)->path())
                        ->all()
                );

                return tap(
                    $func ? $func() : static::create(directories: $directories),
                    fn () => config(['models-collection.directories' => $configuredDirs])
                );
            }
        );
    }

    /**
     * @template TFilePath \Jhavenz\ModelsCollection\Structs\Filesystem\FilePath
     *
     * @template TFilter (Closure(TFilePath $filepath): bool)|TFilePath|class-string<Model>
     *
     * @param  TFilter  ...$filters
     *
     * @return static
     */
    public static function usingFilters(string|Closure|FilePath ...$filters): static
    {
        $models = [];
        foreach ($filters as $filter) {
            if (is_callable($filter) || !($fp = FilePath::from($filter))->isA(Model::class)) {
                continue;
            }

            $models[] = $fp->instance();
        }

        return static::create(models: $models)->addFilters(...$filters);
    }

    public static function withAdditionalDirectories(
        mixed $func = null,
        string|DirectoryPath ...$directories
    ): mixed {
        if (!is_callable($func)) {
            $directories = [$func, ...$directories];
            $func = null;
        }

        foreach ($directories as $d) {
            if (!is_string($d) && !$d instanceof DirectoryPath) {
                throw new ModelCollectionException(
                    "directories must be a string or an instance of [\Jhavenz\ModelsCollection\Structs\Filesystem\DirectoryPath]"
                );
            }
        }

        return self::usingDirectories($func, true, ...$directories);
    }

    public static function withoutConfiguredDirectories(
        mixed $func = null,
        string|DirectoryPath ...$directories
    ): mixed {
        if (!is_callable($func)) {
            $directories = [$func, ...$directories];
            $func = null;
        }

        foreach ($directories as $d) {
            if (!is_string($d) && !$d instanceof DirectoryPath) {
                throw new ModelCollectionException(
                    'directories must be a string or an instance of [\Jhavenz\ModelsCollection\Structs\Filesystem\DirectoryPath]'
                );
            }
        }

        return self::usingDirectories($func, false, ...$directories);
    }

    public function setDirectories(Directories $directories): static
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
        return $this->apply()->models->values()->all();
    }

    public function toBase(): Collection
    {
        return collect($this->toArray());
    }

    public function toClassString(bool $sort = true, $sortFlags = SORT_NATURAL): Collection
    {
        return $this
            ->map(fn ($item) => Path::from($item)->toClassString())
            ->when($sort, fn ($c) => $c->sort($sortFlags))
            ->values();
    }

    public function toDirectories(bool $sort = true, $sortFlags = SORT_NATURAL): Directories
    {
        return $this
            ->toBase()
            ->map(fn (Model $model) => DirectoryPath::from($model::class)->path())
            ->when($sort, fn ($c) => $c->sort($sortFlags))
            ->mapInto(DirectoryPath::class)
            ->unique(fn (DirectoryPath $dir) => $dir->path())
            ->pipeInto(Directories::class);
    }

    private static function toModel(mixed $model): ?Model
    {
        return rescueQuietly(
            fn () => match (true) {
                $model instanceof Model => $model,
                ($fp = FilePath::from($model))->isA(Model::class) => $fp->instance(),
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

        if (!method_exists($collection = $self->toBase(), $method)) {
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

    /**
     * @param  iterable|null  $items
     *
     * @return array
     */
    private static function toModels(?iterable $items = []): array
    {
        return ($items ? collect($items) : static::create()->apply())
            ->map(fn (string|Model|FilePath $item): Model => self::toModel($item))
            ->filter()
            ->all();
    }

    public function hasModel(Model $model): bool
    {
        foreach ($this->models as $m) {
            if ($m::class === $model::class) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  FilePath  $filePath
     *
     * @return bool
     */
    public function passesFilters(FilePath $filePath): bool
    {
        if (!count($this->filters)) {
            return true;
        }

        foreach ($this->filters as $filter) {
            if ($filter($filePath)) {
                return true;
            }
        }

        return false;
    }
}
