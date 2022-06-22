<?php

namespace Jhavenz\ModelsCollection\Iterator;

use FilterIterator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Iterator;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;
use function Jhavenz\rescueQuietly;

/**
 * @template T of <array-key, FilePath>
 *
 * @implements T
 */
class ModelIterator extends FilterIterator implements Arrayable
{
    // optimization
    private static array $accepted = [];

    public function __construct(Iterator $paths, private array $filters = [])
    {
        parent::__construct($paths);
    }

    public function contains(mixed $item): bool
    {
        return in_array(FilePath::factory($item)->path(), self::$accepted);
    }

    public function accept(): bool
    {
        if ($this->contains($current = $this->current())) {
            return true;
        }

        $isModel = rescueQuietly(
            fn () => is_a($current?->instance(), Model::class),
            fn () => false
        );

        ray([
            'isModel' => $isModel,
            'current' => $current
        ]);

        if (! $isModel) {
            return false;
        }

        foreach ($this->filters as $filter) {
            if (! $filter($current)) {
                return false;
            }
        }

        self::$accepted = array_unique([
            ...self::$accepted,
            $current->path()
        ]);

        return true;
    }

    public function current(): ?FilePath
    {
        return ($path = parent::current()) ? FilePath::factory($path) : $path;
    }

    public function toArray(): array
    {
        return iterator_to_array($this);
    }
}
