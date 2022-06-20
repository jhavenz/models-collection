<?php

namespace Jhavenz\ModelsCollection\Tests\Fixtures\Models;

interface IHasMigrations
{
    public function runMigrations(): void;
}
