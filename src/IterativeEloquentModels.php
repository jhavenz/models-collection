<?php declare(strict_types=1);

namespace Jhavens\IterativeEloquentModels;

use Innmind\Immutable\Set;
use Jhavens\IterativeEloquentModels\Exceptions\DirectoryPathException;
use Jhavens\IterativeEloquentModels\Iterator\Models;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\DirectoryPath;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\FilePath;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\Path;
use Symfony\Component\Finder\Finder;

class IterativeEloquentModels
{
    private static string|int|array $depth = [];
    private static Set $directories;

    public function __construct()
    {
        if (app()->bound($class = Models::class)) {
            app()->forgetInstance($class);
        }
    }

    public static function depth(): string|int|array
    {
        return self::$depth;
    }

    /** @return Set<DirectoryPath> */
    public static function directories(): Set
    {
        return match (TRUE) {
            filled(self::$directories ?? null) => self::$directories->filter(fn ($path) => filled($path)),
            app()->resolved(Models::class) => Models::make()->getDirectories(),
            default => Set::strings(self::usingModelsDirectory()::getDefaultDirectory()),
        };
    }

    public static function usingModelsDirectory(null|string|DirectoryPath $path = null): static
    {
        static::addDirectory($path ?? self::getDefaultDirectory());

        return new static;
    }

    /** {@see Finder::depth()} */
    public static function usingDepth(string|int|array $depth): static
    {
        self::$depth = $depth;

        return new self;
    }

    public static function usingDirectories(string|Path ...$directories): static
    {
        if (empty(self::$directories)) {
            self::$directories = Set::of(null);
        }

        foreach ($directories as $directory) {
            self::$directories = self::directories()->add(DirectoryPath::factory($directory));
        }

        return new self;
    }

    public static function only(FilePath|string ...$filters): static
    {
        $paths = array_map(function (string|FilePath $path) {
            return tap(FilePath::factory($path), fn ($p) => self::addDirectory($p));
        }, $filters);

        Models::make()->addFilter(function (FilePath $path) use (&$paths) {
            /** @var FilePath $p */
            foreach ($paths as $p) {
                 if ($path->classString() === $p->classString()) {
                     return true;
                 }
             }

            return false;
        });

        return new static;
    }

    /**
     * @param Path|string $item
     */
    private static function addDirectory(Path|string $item): void
    {
        $path = Path::factory($item)->directoryPath();

        if (isset(self::$directories)
            && self::$directories->contains($path)) {
            return;
        }

        static::usingDirectories($path);
    }

    private static function getDefaultDirectory(): string
    {
        return match (TRUE) {
            is_dir($modelsPath = app_path('Models')) => $modelsPath,
            is_dir($appPath = app_path()) => $appPath,
            default => throw DirectoryPathException::noDefault()
        };
    }

    public static function setDefaultDirectory(?string $path = null): void
    {
        if (! empty(self::$directories)) {
           return;
        }

        self::addDirectory($path ?? self::getDefaultDirectory());
    }
}
