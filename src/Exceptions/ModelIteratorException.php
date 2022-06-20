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

	public static function invalidFilter(string $type): static
    {
        return new static(
            "Unknown filter type: [{$type}]. Filters should be a Model class-string or a closure that returns a boolean"
        );
	}

    public static function invalidFilterReturnType(string $type): static
    {
        return new static(
            "Model filters must return a boolean, [{$type}] returned"
        );
    }
}
