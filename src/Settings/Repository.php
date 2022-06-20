<?php declare(strict_types=1);

namespace Jhavenz\ModelsCollection\Settings;

use Innmind\Immutable\Set;
use Jhavenz\ModelsCollection\Exceptions\DirectoryPathException;
use Jhavenz\ModelsCollection\ModelsCollection;
use Jhavenz\ModelsCollection\Structs\Filesystem\DirectoryPath;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;
use Jhavenz\ModelsCollection\Structs\Filesystem\Path;
use Symfony\Component\Finder\Finder;

class Repository
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
        return self::$depth ?? [];
    }

    /** @return Set<DirectoryPath> */
    public static function directories(): Set
    {
        if (! empty(self::$directories) && filled(array_filter(self::$directories->toList()))) {
            return self::$directories->filter(fn ($path) => filled($path));
        }

        return self::$directories = Set::strings(self::getDefaultDirectory());
    }

    /** {@see Finder::depth()} */
    public static function usingDepth(string|int|array $depth): static
    {
        self::$depth = $depth;

        return new self;
    }

    public static function usingDirectories(string|Path ...$directories): static
    {
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
            config()->has($pkgConfigPath = 'models-collection.models_path') => config($pkgConfigPath),
            is_dir($modelsPath = app_path('Models')) => $modelsPath,
            is_dir($appPath = app_path()) => $appPath,
            default => throw DirectoryPathException::noDefault()
        };
    }

    public static function flush()
    {
        self::$directories = Set::of(null);
        self::$depth = [];
    }
}
