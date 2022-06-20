<?php

namespace Jhavens\IterativeEloquentModels\Structs\Filesystem;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Jhavens\IterativeEloquentModels\IterativeEloquentModels;
use JsonSerializable;
use SplFileInfo;
use Stringable;
use Symfony\Component\Finder\Comparator as SymfonyComparator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as SymfonyFileInfo;
use const PHP_INT_MAX;

abstract class Path implements Stringable, JsonSerializable
{
    abstract protected function validate(): void;

    public function __construct(readonly private string $path)
    {
        $this->validate();
    }

    public function directoryPath(): string
    {
        return $this->path();
    }

    /** @return Finder<SymfonyFileInfo> */
    public function fileFinder(): Finder
    {
        return $this->makeFinderForPath($this->path(), filesOnly: true);
    }

    public static function from(mixed $path): static
    {
        return new static($path);
    }

    public static function factory(mixed $path): Path
    {
        return match (TRUE) {
            is_string($path) && class_exists($path) => FilePath::fromClassString($path),
            $path instanceof Path => $path,
            $path instanceof SplFileInfo => FilePath::from($path->getRealPath()),
            $path instanceof Model => FilePath::fromClassString($path::class),
            is_file($path) => FilePath::from($path),
            is_dir($path) => DirectoryPath::from($path),
            default => throw new InvalidArgumentException("[{$path}] was not found to be a file or a directory path")
        };
    }

    public function path(): string
    {
        return $this->path;
    }

    public function __toString()
    {
        return $this->path();
    }

    /** @noinspection PhpMissingReturnTypeInspection */
    public function jsonSerialize()
    {
        return (string) $this;
    }

    public function makeFinderForPath(string $path, bool $filesOnly = false, bool $directoriesOnly = false): Finder
    {
        if ($filesOnly && is_file($path)) {
            $path = dirname($path);
        }

        return tap(
            Finder::create()
                ->in($path)
                ->ignoreVCS(true)
                ->ignoreDotFiles(true),
            function (Finder $finder) use ($directoriesOnly, $filesOnly) {
                foreach ([
                    fn ($f) => filled($depth = IterativeEloquentModels::depth()) && $f->depth($depth),
                    fn ($f) => $filesOnly && $f->files(),
                    fn ($f) => $directoriesOnly && $f->directories()
                ] as $configuration) {
                    $configuration($finder);
                }
            }
        );
    }

    private function getMinMaxDepth(): array
    {
        $minDepth = 0;
        $maxDepth = PHP_INT_MAX;

        $depths = [];
        foreach ((array) IterativeEloquentModels::depth() as $d) {
            $depths[] = new SymfonyComparator\NumberComparator($d);
        }

        /** @var SymfonyComparator\NumberComparator $comparator */
        foreach ($depths as $comparator) {
            switch ($comparator->getOperator()) {
                case '>':
                    /** @noinspection PhpWrongStringConcatenationInspection */
                    $minDepth = $comparator->getTarget() + 1;
                    break;
                case '>=':
                    $minDepth = $comparator->getTarget();
                    break;
                case '<':
                    $maxDepth = $comparator->getTarget() - 1;
                    break;
                case '<=':
                    $maxDepth = $comparator->getTarget();
                    break;
                default:
                    $minDepth = $maxDepth = $comparator->getTarget();
            }
        }

        return [$minDepth, $maxDepth];
    }
}
