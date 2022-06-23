<?php

namespace Jhavenz\ModelsCollection\Structs\Filesystem;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Jhavenz\ModelsCollection\Settings\Repository;
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

    public function __construct(
        readonly private string $path
    ) {
        $this->validate();
    }

    public function directoryPath(): string
    {
        return $this->path();
    }

    /** @return Finder<SymfonyFileInfo> */
    public function fileFinder(): Finder
    {
        return $this->finder ??= $this->makeFinderForPath($this->path(), filesOnly: true);
    }

    public static function from(mixed $path): static
    {
        return new static($path);
    }

    public static function factory(mixed $path): Path
    {
        return match (TRUE) {
            $path instanceof Path => $path,
            $path instanceof Model => FilePath::fromClassString($path::class),
            $path instanceof SplFileInfo => FilePath::from($path->getRealPath()),
            is_string($path) && is_file($path) => FilePath::from($path),
            is_string($path) && is_dir($path) => DirectoryPath::from($path),
            is_string($path) && class_exists($path) => FilePath::fromClassString($path),
            default => InvalidPath::from($path)
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
    #[\ReturnTypeWillChange]
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
					fn ($f) => $filesOnly && $f->files(),
					fn ($f) => $directoriesOnly && $f->directories()
                ] as $configuration) {
                    $configuration($finder);
                }
            }
        );
    }

    public function toClassString(): null|string|Collection
    {
        return null;
    }

    private function getMinMaxDepth(): array
    {
        $minDepth = 0;
        $maxDepth = PHP_INT_MAX;

        $depths = [];
        foreach ((array) Repository::depth() as $d) {
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
