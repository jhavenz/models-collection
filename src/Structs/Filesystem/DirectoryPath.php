<?php

namespace Jhavenz\ModelsCollection\Structs\Filesystem;

use Illuminate\Support\Collection;
use Jhavenz\ModelsCollection\Exceptions\DirectoryPathException;
use Jhavenz\ModelsCollection\Exceptions\ModelIteratorException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as SymfonyFileInfo;

class DirectoryPath extends Path
{
    private Finder $finder;
    private Collection $files;

    public static function factory(mixed $path): DirectoryPath
    {
        $path = parent::factory($path);

        return $path instanceof self ? $path : self::from($path->directoryPath());
    }

    /** @return Collection<FilePath> */
    public function getFiles(): Collection
    {
        return $this->files ??= collect($this->fileFinder())->map(fn (SymfonyFileInfo $fileInfo) =>
            FilePath::from($fileInfo->getRealPath())
        );
    }

    public function require(): static
    {
        $this->getFiles()->each(fn (FilePath $fp) => $fp->require());

        return $this;
    }

    public function toClassString(): null|string|Collection
    {
        return $this->getFiles()->map(fn (FilePath $f) => $f->toClassString());
    }

    protected function validate(): void
    {
        if (! is_dir($this->path())) {
            throw DirectoryPathException::invalidPath($this->path());
        }

        if (is_link($this->path())) {
            throw ModelIteratorException::noSymlinksAllowed($this->path());
        }
    }
}
