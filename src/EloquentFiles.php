<?php

namespace Jhavenz\ModelsCollection;

use Illuminate\Database\Eloquent\Model;
use Jhavenz\PhpStructs\Filesystem\Exceptions\InvalidPathException;
use Jhavenz\PhpStructs\Filesystem\Local\Collections\Directories;
use Jhavenz\PhpStructs\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class EloquentFiles extends Directories
{
    public static function fromConfig(): static
    {
        $finder = Finder::create()
            ->in(config('models-collection.directories'))
            ->files()
            ->depth(['0']);

        $data = array_filter(iterator_to_array($finder), function (SplFileInfo $fileInfo) {
            $path = Path::make($fileInfo);

            try {
                return class_exists($fqcn = $path->getFileParser()->getFqcn())
                    && !$path->isDirectory()
                    && !interface_exists($fqcn)
                    && $path->isA(Model::class);
            } catch (InvalidPathException $e) {
                return false;
            }
        });

        return new static($data);
    }
}
