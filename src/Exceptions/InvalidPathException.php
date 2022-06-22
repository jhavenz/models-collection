<?php

namespace Jhavenz\ModelsCollection\Exceptions;

use RuntimeException;

class InvalidPathException extends RuntimeException
{
    public static function existingResourceWasFound(string $path): static
    {
        return new static(
            "[{$path}] is a valid file or directory"
        );
    }
}
