<?php

namespace Jhavenz\ModelsCollection\Structs\Filesystem;

use Jhavenz\ModelsCollection\Exceptions\FilePathException;
use Jhavenz\ModelsCollection\Exceptions\ModelIteratorException;
use PhpClass\PhpClass;
use ReflectionClass;

class FilePath extends Path
{
    private object $instance;
    private ReflectionClass $reflectionClass;

    public function classString(): string
    {
        return get_class($this->instance());
    }

    public function directoryPath(): string
    {
        return dirname($this->path());
    }

    public static function factory(mixed $path): FilePath
    {
        $path = parent::factory($path);

        if (! $path instanceof self) {
            throw FilePathException::invalidPath($path->path());
        }

        return $path;
    }

    public function instance(): object
    {
        return $this->instance ??= (new PhpClass($this->path()))->instantiate();
    }

    public function instantiate(): object
    {
        try {
            return $this->instance();
        } catch (\Throwable $e) {
            throw FilePathException::unableToInstantiate($this->path());
        }
    }

    public function reflectionClass(): ReflectionClass
    {
        return $this->reflectionClass ??= new ReflectionClass(get_class($this->instance()));
    }

    protected function validate(): void
    {
        if (! is_file($this->path())) {
            throw FilePathException::invalidPath($this->path());
        }

        if (is_link($this->path())) {
            throw ModelIteratorException::noSymlinksAllowed($this->path());
        }
    }

    public static function fromClassString(string $class): static
    {
        $path = new ReflectionClass($class);

        return new static($path->getFileName());
    }
}
