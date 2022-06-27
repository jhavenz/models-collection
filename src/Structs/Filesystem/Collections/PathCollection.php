<?php

namespace Jhavenz\ModelsCollection\Structs\Filesystem\Collections;

use Illuminate\Support\Collection;
use Jhavenz\ModelsCollection\Structs\Filesystem\DirectoryPath;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;
use Jhavenz\ModelsCollection\Structs\Filesystem\Path;
use LogicException;

use function Jhavenz\rescueQuietly;

/**
 * @template T Path
 *
 * @implements PathCollection<array-key, T>
 */
abstract class PathCollection extends Collection
{
    /** @var class-string<FilePath|DirectoryPath> */
    protected string $class;

    protected function getArrayableItems($items): array
    {
        if (empty($this->class)) {
            throw new LogicException('class property must be defined within the constructor of the child collection');
        }

        return array_reduce(parent::getArrayableItems($items), function ($carry, $path) {
            $item = rescueQuietly(
                fn() => $this->class::factory($path),
            );

            if ($item && is_a($item, $this->class, true)) {
                $carry[] = new $this->class($item);
            }

            return $carry;
        }, []);
    }

    public function instantiate(): Collection
    {
        return $this->flatMap(fn(Path $path) => match ($path::class) {
            FilePath::class => $path->instance(),
            DirectoryPath::class => $path->getFiles()->map(fn(FilePath $filePath) => $filePath->instance()),
            default => null
        });
    }
}
