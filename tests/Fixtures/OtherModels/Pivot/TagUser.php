<?php

namespace Jhavenz\ModelsCollection\Tests\Fixtures\OtherModels\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TagUser extends Pivot
{
    public function runMigrations(): void
    {
        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('tag_id')->constrained('tags');
        });
    }
}
