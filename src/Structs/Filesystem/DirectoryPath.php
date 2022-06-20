<?php

namespace Jhavenz\ModelsCollection\Structs\Filesystem;

use Jhavenz\ModelsCollection\Exceptions\DirectoryPathException;
use Jhavenz\ModelsCollection\Exceptions\ModelIteratorException;

class DirectoryPath extends Path
{
    public static function factory(mixed $path): Path
    {
        $path = parent::factory($path);

        return $path instanceof self ? $path : self::from($path->directoryPath());
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
