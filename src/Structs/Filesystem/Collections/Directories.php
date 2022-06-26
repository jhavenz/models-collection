<?php

namespace Jhavenz\ModelsCollection\Structs\Filesystem\Collections;

use Jhavenz\ModelsCollection\Structs\Filesystem\DirectoryPath;
use Jhavenz\ModelsCollection\Structs\Filesystem\FilePath;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @extends PathCollection
 */
class Directories extends PathCollection
{
    protected string $class = DirectoryPath::class;

    public function toFiles(int|string|array $depth = []): Files
    {
        $return = Files::make();

        /** @var DirectoryPath $directory */
        foreach ($this as $directory) {
            $finder = $directory->fileFinder();

            if (filled($depth) || is_int($depth)) {
                $finder->depth($depth);
            }

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $return[] = FilePath::factory($file);
            }
        }

        return $return;
    }
}
