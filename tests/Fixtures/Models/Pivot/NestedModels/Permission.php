<?php

namespace Jhavenz\ModelsCollection\Tests\Fixtures\Models\Pivot\NestedModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Permission extends Model
{
    public function runMigrations(): void
    {
        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }
}
