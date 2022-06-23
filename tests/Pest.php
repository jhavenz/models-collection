<?php

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Jhavenz\ModelsCollection\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function schema(string $connection = 'testing'): Builder
{
    return Schema::connection($connection);
}
