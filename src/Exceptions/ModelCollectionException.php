<?php

namespace Jhavenz\ModelsCollection\Exceptions;

use Closure;
use RuntimeException;

class ModelCollectionException extends RuntimeException
{
    public static function invalidFilter(Closure|string $filter): static
    {
        return new static(
            'A ['.gettype($filter).'] filter is invalid. ModelCollection filters must be a class-string<Model> or Closure'
        );
    }
}
