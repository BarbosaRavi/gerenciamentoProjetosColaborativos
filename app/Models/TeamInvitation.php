<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'invited_by',
        'email',
        'status',
        'token',
    ];

    public function team(): BelongsTo {
        return $this->belongsTo(Team::class);
    }

    public function inviter(): BelongsTo {
        return $this->belongsTo(User::class, 'invited_by');
    }
}