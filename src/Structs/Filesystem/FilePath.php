<?php declare(strict_types=1);

namespace Jhavenz\ModelsCollection\Structs\Filesystem;

use Jhavenz\ModelsCollection\Exceptions\FilePathException;
use Jhavenz\ModelsCollection\Exceptions\ModelIteratorException;
use PhpClass\PhpClass;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\SplFileInfo as SymfonyFileInfo;

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

    public static function fromClassString(string $class): static
    {
        $path = new ReflectionClass($class);

        return new static($path->getFileName());
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

    public function relativePath(): string
    {
        return str($this->relativePathname())->beforeLast(DIRECTORY_SEPARATOR)->toString();
    }

    public function relativePathname(): string
    {
        $ds = DIRECTORY_SEPARATOR;

        return collect(explode($ds, $this->path()))
            ->skipUntil(fn (string $segment) => str_contains($segment, basename(base_path())))
            ->prepend($ds)
            ->join($ds);
    }

    public function require(): static
    {
        require_once $this->path();

        return $this;
    }

    public function toClassString(): ?string
    {
        return get_class($this->instance());
    }

    public function toFileInfo(): SplFileInfo
    {
        return new SplFileInfo($this->path());
    }

    public function toSymfonyFileInfo(): SymfonyFileInfo
    {
        return new SymfonyFileInfo($this->path(), $this->relativePath(), $this->relativePathname());
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
}
