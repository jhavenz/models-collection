<?php

namespace Jhavenz\ModelsCollection;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Jhavenz\PhpStructs\Filesystem\FilePath;
use Nette\Utils\Html;

if (!function_exists('invokeSafe')) {
    /**
     * Function copied from \Nette\Utils\Callback::invokeSafe of the Nette Framework (https://nette.org)
     * This version allows a callable as the 1st param, vs a requiring a string
     */
    function invokeSafe(callable $function, array $args, callable $onError): mixed
    {
        $prev = set_error_handler(function ($severity, $message, $file) use ($onError, &$prev, $function): ?bool {
            if ($file === __FILE__) {
                $msg = ini_get('html_errors')
                    ? Html::htmlToText($message)
                    : $message;
                $msg = preg_replace("#^$function\\(.*?\\): #", '', $msg);
                if ($onError($msg, $severity) !== false) {
                    return null;
                }
            }

            return $prev ? $prev(...func_get_args()) : false;
        });

        try {
            return $function(...$args);
        }
        finally {
            restore_error_handler();
        }
    }
}

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
     * @param  TValue  $value
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

if (!function_exists('srcPath')) {
    function srcPath(?string $path = null): string
    {
        return str($path ?? '')
            ->trim(DIRECTORY_SEPARATOR)
            ->whenNotEmpty(
                fn (Stringable $str) => $str->wrap(DIRECTORY_SEPARATOR),
                fn (Stringable $str) => $str->prepend(DIRECTORY_SEPARATOR)
            )
            ->prepend(__DIR__)
            ->rtrim(DIRECTORY_SEPARATOR)
            ->toString();
    }
}

if (!function_exists('basePath')) {
    function basePath(?string $path = null): string
    {
        return str($path ?? '')
            ->trim(DIRECTORY_SEPARATOR)
            ->whenNotEmpty(
                fn (Stringable $str) => $str->wrap(DIRECTORY_SEPARATOR),
                fn (Stringable $str) => $str->prepend(DIRECTORY_SEPARATOR)
            )
            ->prepend(Str::beforeLast(srcPath(), DIRECTORY_SEPARATOR.'src'))
            ->rtrim(DIRECTORY_SEPARATOR)
            ->toString();
    }
}
