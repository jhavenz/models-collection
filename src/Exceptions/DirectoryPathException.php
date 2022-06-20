<?php

namespace Jhavenz\ModelsCollection\Exceptions;

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
            "No default Models directory could be found. If not using a conventional Models directory, call \Jhavenz\ModelsCollection\IterativeEloquentModels::usingModelsDirectory() to set a custom one"
        );
    }
}
