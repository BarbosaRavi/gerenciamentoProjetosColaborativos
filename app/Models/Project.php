<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'team_id',
    ];

    protected function casts(): array {
        return [
            'owner_id' => 'integer',
        ];
    }

    public function owner(): BelongsTo {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function team(): BelongsTo {
        return $this->belongsTo(Team::class);
    }

    public function members(): BelongsToMany {
        return $this->belongsToMany(User::class, 'project_user')
            ->withTimestamps();
    }

    public function tasks(): HasMany {
        return $this->hasMany(Task::class);
    }

    public function invitations(): HasMany {
        return $this->hasMany(ProjectInvitation::class);
    }

    public function tags(): HasMany {
        return $this->hasMany(Tag::class);
    }
}