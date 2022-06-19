<?php

namespace Jhavens\IterativeEloquentModels\Exceptions;

use RuntimeException;

class DirectoryPathException extends RuntimeException
{
    public static function invalidPath(string $path): static
    {
        return new static(
            "No directory found at path: [{$path}]. Try using Directory::factory() instead?"
        );
    }

    public static function noDefault(): static
    {
        return new static(
            "No default Models directory could be found. If not using a conventional Models directory, call \Jhavens\IterativeEloquentModels\IterativeEloquentModels::usingModelsDirectory() to set a custom one"
        );
    }
}
