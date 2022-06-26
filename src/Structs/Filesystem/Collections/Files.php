<?php

namespace Jhavenz\ModelsCollection\Structs\Filesystem\Collections;

use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;

/**
 * @extends PathCollection
 */
class Files extends PathCollection
{
    protected string $class = FilePath::class;

    public function add($item)
    {
        $this->items = [
            ...$this->items,
            ...static::make([$item])->all(),
        ];

        return $this;
    }
}
