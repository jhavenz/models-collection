<?php

namespace Jhavenz\ModelsCollection\Structs\Filesystem;

use Illuminate\Support\Collection;
use Jhavenz\ModelsCollection\Exceptions\InvalidPathException;

class InvalidPath extends Path
{
    public static function fromClassString(string $class): static
    {
        return new static('');
    }

    public function getFiles(): Collection
    {
        return collect();
    }

    public function instantiate(): ?object
    {
        return null;
    }

    public function toClassString(): ?string
    {
        return null;
    }

    protected function validate(): void
	{
		if (is_file($path = $this->path()) || is_dir($path)) {
            throw InvalidPathException::existingResourceWasFound($path);
        }
	}

    public function __toString()
    {
        return $this->path().' is an invalid path';
    }
}
