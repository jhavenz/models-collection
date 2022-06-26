<?php

namespace Jhavenz\ModelsCollection\Exceptions;

use League\Flysystem\SymbolicLinkEncountered;
use RuntimeException;
use Throwable;

class ModelIteratorException extends RuntimeException
{
    public static function noSymlinksAllowed(string $path): Throwable
    {
        return SymbolicLinkEncountered::atLocation($path);
    }

    public static function invalidFilterReturnType(string $type): static
    {
        return new static(
            "Model filters must return a boolean, [{$type}] returned"
        );
    }
}
