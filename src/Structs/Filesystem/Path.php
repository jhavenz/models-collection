<?php

namespace Jhavenz\ModelsCollection\Structs\Filesystem;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonSerializable;
use SplFileInfo;
use Stringable;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as SymfonyFileInfo;

abstract class Path implements Stringable, JsonSerializable
{
    use ForwardsCalls;

    abstract public function isA(string $class): bool;

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
    public function fileFinder(bool $filesOnly = true, bool $directoriesOnly = false): Finder
    {
        return $this->finder ??= $this->makeFinderForPath($this->path(), $filesOnly, $directoriesOnly);
    }

    public static function from(mixed $path): static
    {
        return new static($path);
    }

    public static function factory(mixed $path): Path
    {
        return match (true) {
            $path instanceof Path => $path,
            $path instanceof Model => FilePath::fromClassString($path::class),
            $path instanceof SplFileInfo && $path->isFile() => FilePath::from($path->getRealPath()),
            $path instanceof SplFileInfo && $path->isDir() => DirectoryPath::from($path->getRealPath()),
            is_string($path) && is_file($path) => FilePath::from($path),
            is_string($path) && is_dir($path) => DirectoryPath::from($path),
            is_string($path) && class_exists($path) => FilePath::fromClassString($path),
            is_string($path) => InvalidPath::from($path),
            is_object($path) => InvalidPath::from($path::class),
            default => InvalidPath::from('')
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
        return (string)$this;
    }

    protected function makeFinderForPath(string $path, bool $filesOnly = false, bool $directoriesOnly = false): Finder
    {
        if ($filesOnly && is_file($path)) {
            $path = dirname($path);
        }

        return tap(
            Finder::create()
                ->in($path)
                ->ignoreUnreadableDirs()
                ->ignoreVCS(true)
                ->ignoreVCSIgnored(true)
                ->ignoreDotFiles(true),
            function (Finder $finder) use ($directoriesOnly, $filesOnly) {
                foreach (
                    [
                        fn($f) => $filesOnly && $f->files(),
                        fn($f) => $directoriesOnly && $f->directories(),
                    ] as $configuration
                ) {
                    $configuration($finder);
                }
            }
        );
    }

    public function relativePath(): string
    {
        return str($this->relativePathname())->beforeLast(DIRECTORY_SEPARATOR)->toString();
    }

    public function relativePathname(): string
    {
        $ds = DIRECTORY_SEPARATOR;

        return collect(explode($ds, $this->path()))
            ->skipUntil(fn(string $segment) => str_contains($segment, basename(base_path())))
            ->prepend($ds)
            ->join($ds);
    }

    public function toClassString(): null|string|Collection
    {
        return null;
    }

    public function toFileInfo(): SplFileInfo
    {
        return new SplFileInfo($this->path());
    }

    public function toSymfonyFileInfo(): SymfonyFileInfo
    {
        return new SymfonyFileInfo($this->path(), $this->relativePath(), $this->relativePathname());
    }

    public function __call(string $method, array $parameters)
    {
        try {
            return $this->forwardCallTo($this->toSymfonyFileInfo(), $method, $parameters);
        } catch (BadMethodCallException) {
            throw new BadMethodCallException(
                sprintf(
                    'Call to undefined method %s::%s()',
                    static::class,
                    $method
                )
            );
        }
    }
}
