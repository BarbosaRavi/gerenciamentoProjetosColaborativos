<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_id',
    ];

    protected function casts(): array {
        return [
            'owner_id' => 'integer',
        ];
    }

    public function owner(): BelongsTo {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany {
        return $this->belongsToMany(User::class, 'team_user')
            ->withTimestamps();
    }

    public function projects(): HasMany {
        return $this->hasMany(Project::class);
    }

    public function invitations(): HasMany {
        return $this->hasMany(TeamInvitation::class);
    }
}