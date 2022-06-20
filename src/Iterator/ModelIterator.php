<?php

namespace Jhavenz\ModelsCollection\Iterator;

use FilterIterator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Innmind\Immutable\Set;
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
    private static Set $accepted;

    public function __construct(Iterator $paths, private array $customFilters = [])
    {
        self::$accepted ??= Set::strings();

        parent::__construct($paths);
    }

    public function contains(Model|string|FilePath $item): bool
    {
        return self::$accepted->contains(FilePath::factory($item)->path());
    }

    public function accept(): bool
    {
        $current = $this->current();

        $isModel = rescueQuietly(
            fn () => is_a($current?->instance(), Model::class),
            fn () => false
        );

        if (! $isModel) {
            return false;
        }

        foreach ($this->customFilters as $filter) {
            if (! $filter($current)) {
                return false;
            }
        }

        $this->addAcceptedPath($current);

        return true;
    }

    public function current(): ?FilePath
    {
        $current = parent::current();

        return match(TRUE) {
            is_string($current) => FilePath::from($current),
            $current instanceof FilePath => $current,
            default => null
        };
    }

    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    /**
     * @param FilePath $fp
     */
    private function addAcceptedPath(FilePath $fp): void
    {
        self::$accepted = self::$accepted->merge(Set::strings($fp->path()));
    }
}
