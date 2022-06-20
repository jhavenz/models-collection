<?php declare(strict_types=1);

namespace Jhavens\IterativeEloquentModels;

use Innmind\Immutable\Set;
use Jhavens\IterativeEloquentModels\Exceptions\DirectoryPathException;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\DirectoryPath;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\FilePath;
use Jhavens\IterativeEloquentModels\Structs\Filesystem\Path;
use Symfony\Component\Finder\Finder;

class IterativeEloquentModels
{
    private static Set $directories;
    private static string|int|array $depth;

    public function __construct()
    {
        if (app()->bound($class = ModelsCollection::class)) {
            app()->forgetInstance($class);
        }
    }

    public static function depth(): string|int|array
    {
        // infinite depth by default
        return self::$depth ?? ['>= 0'];
    }

    /** @return Set<DirectoryPath> */
    public static function directories(): Set
    {
        if (empty(self::$directories)) {
            self::$directories = Set::strings(self::getDefaultDirectory());
        }

        return self::$directories->filter(fn ($path) => filled($path));
        return match (TRUE) {
            empty(self::$directories) => self::usingDirectories(self::getDefaultDirectory()),
            //app()->resolved(Models::class) => Models::make()->getDirectories(),
            default => self::$directories->filter(fn ($path) => filled($path)),
            //filled(self::$directories ?? null) => self::$directories->filter(fn ($path) => filled($path)),
            //default => Set::strings(self::usingModelsDirectory()::getDefaultDirectory()),
        };
    }

    /** {@see Finder::depth()} */
    public static function usingDepth(string|int|array $depth): static
    {
        self::$depth = $depth;

        return new self;
    }

    public static function usingDirectories(string|Path ...$directories): static
    {
        //if (! empty(self::$directories)) {
        //    self::$directories = Set::of(null);
        //}

        foreach ($directories as $directory) {
            self::$directories = self::directories()->add(DirectoryPath::factory($directory)->directoryPath());
        }

        return new self;
    }

    public static function only(FilePath|string ...$filters): static
    {
        $paths = array_map(function (string|FilePath $path) {
            return tap(FilePath::factory($path), fn ($p) => self::usingDirectories($p));
        }, $filters);

        ModelsCollection::create()->addFilter(function (FilePath $path) use (&$paths) {
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

    private static function getDefaultDirectory(): string
    {
        return match (TRUE) {
            config()->has($appConfigPath = 'app.models_path') => config($appConfigPath),
            config()->has($pkgConfigPath = 'iterative-eloquent-models.models_path') => config($pkgConfigPath),
            is_dir($modelsPath = app_path('Models')) => $modelsPath,
            is_dir($appPath = app_path()) => $appPath,
            default => throw DirectoryPathException::noDefault()
        };
    }
}
