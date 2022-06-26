<?php

namespace Jhavenz;

use Closure;
use Illuminate\Support\Collection;
use Jhavenz\ModelsCollection\ModelsCollection;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;

if (!function_exists('rescueQuietly')) {
    /** no reporting if/when an exception is thrown */
    function rescueQuietly(callable $try, ?callable $catch = null)
    {
        return rescue($try, $catch, false);
    }
}

if (!function_exists('toIterable')) {
    /**
     * @noinspection PhpDocSignatureInspection
     *
     * @template     TValue
     *
     * @param TValue $value
     *
     * @return array<TValue>|TValue
     */
    function toIterable(mixed $value): iterable
    {
        return match (true) {
            is_iterable($value) => $value,
            default => [$value],
        };
    }
}

if (!function_exists('filteredModelsCollection')) {
    function filteredModelsCollection(string|Closure|FilePath ...$filters): Collection
    {
        return ModelsCollection::usingFilters(...func_get_args())->toBase();
    }
}

if (!function_exists('modelsCollection')) {
    function modelsCollection(
        ?iterable $files = null,
        ?iterable $directories = null,
        ?iterable $models = null,
        ?iterable $filters = [],
        int|string|array $depth = []
    ): Collection {
        return ModelsCollection::create(...func_get_args())->toBase();
    }
}

if (!function_exists('removeConfiguredDirectories')) {
    function removeConfiguredDirectories(): void
    {
        config(['models-collection.directories' => []]);
    }
}
