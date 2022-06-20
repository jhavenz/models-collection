<?php

namespace Jhavenz\ModelsCollection\Tests\Fixtures\Models\Pivot;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\IHasMigrations;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\Role;
use Jhavenz\ModelsCollection\Tests\Fixtures\Models\User;

class RoleUser extends Pivot implements IHasMigrations
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function runMigrations(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained();
            $table->foreignId('role_id')->constrained();
        });
    }
}
