<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';

    // La transition submitted -> under_review est déclenchée par
    // reviewer_submission, l'assignation fine de relecture par soumission.
    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_REVISION_REQUIRED = 'revision_required';

    public const STATUS_REVISED_SUBMITTED = 'revised_submitted';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CAMERA_READY_SUBMITTED = 'camera_ready_submitted';

    protected $fillable = [
        'conference_id',
        'topic_id',
        'conference_session_id',
        'author_id',
        'title',
        'abstract',
        'keywords',
        'initial_file_path',
        'revised_file_path',
        'camera_ready_file_path',
        'status',
        'submitted_at',
        'revised_at',
        'camera_ready_submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'submitted_at' => 'datetime',
            'revised_at' => 'datetime',
            'camera_ready_submitted_at' => 'datetime',
        ];
    }

    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function conferenceSession(): BelongsTo
    {
        return $this->belongsTo(ConferenceSession::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function coAuthors(): HasMany
    {
        return $this->hasMany(SubmissionAuthor::class)->orderBy('position');
    }

    public function reviewerSubmissions(): HasMany
    {
        return $this->hasMany(ReviewerSubmission::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class)->orderByDesc('decided_at');
    }

    public function latestDecision(): HasOne
    {
        return $this->hasOne(Decision::class)->latestOfMany('decided_at');
    }

    public function authorResponses(): HasMany
    {
        return $this->hasMany(AuthorResponse::class);
    }

    public function publicReviews(): HasManyThrough
    {
        return $this->hasManyThrough(
            Review::class,
            ReviewerSubmission::class,
            'submission_id', // Foreign key on reviewer_submission table
            'reviewer_submission_id', // Foreign key on reviews table
            'id', // Local key on submissions table
            'id'  // Local key on reviewer_submission table
        )
            ->whereNotNull('reviews.submitted_at')
            ->where('reviews.conflict_of_interest', false);
    }
}
