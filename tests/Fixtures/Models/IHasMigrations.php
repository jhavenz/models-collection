<?php

namespace Jhavens\IterativeEloquentModels\Tests\Fixtures\Models;

interface IHasMigrations
{
    public function runMigrations(): void;
}
