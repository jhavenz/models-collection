<?php

namespace Jhavens\IterativeEloquentModels\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Post extends Model implements IHasMigrations
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function runMigrations(): void
    {
        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->foreignId('user_id')->constrained('users');
        });
    }
}
