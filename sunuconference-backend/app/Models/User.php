<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_AUTHOR = 'author';

    public const ROLE_REVIEWER = 'reviewer';

    public const ROLE_CHAIR = 'chair';

    public const ROLE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'affiliation',
        'role',
        'password',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function chairedConferences(): HasMany
    {
        return $this->hasMany(Conference::class, 'chair_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'author_id');
    }

    public function reviewingConferences(): BelongsToMany
    {
        return $this->belongsToMany(Conference::class, 'conference_reviewers', 'user_id', 'conference_id')
            ->withPivot('invited_at')
            ->withTimestamps();
    }

    public function assignedTopics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'reviewer_topic', 'reviewer_id', 'topic_id')
            ->withTimestamps();
    }

    public function reviewSubmissionAssignments(): HasMany
    {
        return $this->hasMany(ReviewerSubmission::class, 'reviewer_id');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
