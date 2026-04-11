<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

    protected string $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ownedTeams(): HasMany {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function teams(): BelongsToMany {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withTimestamps();
    }

    public function ownedProjects(): HasMany {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function projects(): BelongsToMany {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withTimestamps();
    }

    public function createdTasks(): HasMany {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function assignedTasks(): BelongsToMany {
        return $this->belongsToMany(Task::class, 'task_user')
            ->withTimestamps();
    }

    public function comments(): HasMany {
        return $this->hasMany(Comment::class);
    }

    public function getJWTIdentifier(): mixed {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array {
        return [];
    }


}
