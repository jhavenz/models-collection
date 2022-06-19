<?php

namespace Jhavens\IterativeEloquentModels\Structs\Filesystem;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Jhavens\IterativeEloquentModels\IterativeEloquentModels;
use JsonSerializable;
use SplFileInfo;
use Stringable;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as SymfonyFileInfo;

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
        $finder = Finder::create()
            ->in($this->path())
            ->ignoreVCS(true)
            ->ignoreDotFiles(true);
            //->filter(function (SymfonyFileInfo $fileInfo) {
            //    return ! $fileInfo->isDir();
            //});

        if (filled($depth = IterativeEloquentModels::depth())) {
            $finder->depth($depth);
        }

        /** @var SymfonyFileInfo $fileInfo */
        foreach ($finder as $fileInfo) {
            if ($fileInfo->isDir()) {
                $finder = clone $finder->path($fileInfo->getRealPath());
            }
        }



        return $finder;
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
}
