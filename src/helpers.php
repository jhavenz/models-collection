<?php

namespace Jhavenz;


use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Jhavenz\ModelsCollection\ModelsCollection;

if (! function_exists('rescueQuietly')) {
    /** no reporting if/when an exception is thrown */
    function rescueQuietly(callable $try, ?callable $catch = null)
    {
        return rescue($try, $catch, false);
    }
}

if (! function_exists('toIterable')) {
    /**
     * @noinspection PhpDocSignatureInspection
     *
     * @template TValue
     *
     * @param TValue $value
     *
     * @return array<TValue>|TValue
     */
    function toIterable(mixed $value): iterable
    {
        return match (TRUE) {
            is_iterable($value) => $value,
            default => [$value],
        };
    }
}

if (! function_exists('models')) {
    /** @return Collection<array-key, Model> */
    function models(): Collection
    {
        return ModelsCollection::toBase();
    }
}

if (! function_exists('eloquentModels')) {
    /** @return EloquentCollection<array-key, Model> */
    function eloquentModels(): EloquentCollection
    {
        return ModelsCollection::make();
    }
}

if (! function_exists('removeConfiguredDirectories')) {
    function removeConfiguredDirectories(): void
    {
        config(['models-collection.directories' => []]);
    }
}
