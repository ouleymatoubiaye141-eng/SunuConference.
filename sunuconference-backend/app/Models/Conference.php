<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conference extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'chair_id',
        'name',
        'acronym',
        'description',
        'call_for_papers',
        'start_date',
        'end_date',
        'submission_deadline',
        'review_deadline',
        'revision_deadline',
        'camera_ready_deadline',
        'status',
        'rejection_reason',
        'program_published',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'submission_deadline' => 'datetime',
            'review_deadline' => 'datetime',
            'revision_deadline' => 'datetime',
            'camera_ready_deadline' => 'datetime',
            'program_published' => 'boolean',
        ];
    }

    public function chair()
    {
        return $this->belongsTo(User::class, 'chair_id');
    }

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ConferenceSession::class);
    }

    public function reviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conference_reviewers', 'conference_id', 'user_id')
            ->withPivot('invited_at')
            ->withTimestamps();
    }
}
