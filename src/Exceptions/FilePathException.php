<?php

namespace Jhavenz\ModelsCollection\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

class FilePathException extends RuntimeException
{
    public static function invalidClass(string $classString): Throwable
    {
        $eloquentModel = Model::class;

        return new static("[{$classString}] must be an instance of [{$eloquentModel}]");
    }

    public static function invalidPath(string $path): static
    {
        return new static(
            "No file found at path: [{$path}]. Try using FilePath::factory(), or this class' directory may not be listed in your [models-collection.directories] configuration"
        );
    }

    public static function unableToInstantiate(string $path): Throwable
    {
        return new static(
            "Unable to instantiate class at path: [{$path}]"
        );
    }
}
