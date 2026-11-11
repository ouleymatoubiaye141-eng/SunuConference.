<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    public const RECOMMENDATION_ACCEPT = 'accept';

    public const RECOMMENDATION_MINOR_REVISION = 'minor_revision';

    public const RECOMMENDATION_MAJOR_REVISION = 'major_revision';

    public const RECOMMENDATION_REJECT = 'reject';

    protected $fillable = [
        'reviewer_submission_id',
        'originality_score',
        'relevance_score',
        'presentation_score',
        'overall_score',
        'public_comment',
        'private_comment',
        'recommendation',
        'conflict_of_interest',
        'conflict_reason',
        'submitted_at',
    ];

    protected $appends = [
        'status',
    ];

    protected function casts(): array
    {
        return [
            'conflict_of_interest' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function reviewerSubmission(): BelongsTo
    {
        return $this->belongsTo(ReviewerSubmission::class);
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => match (true) {
                $this->conflict_of_interest => 'conflict_declared',
                $this->submitted_at !== null => 'submitted',
                default => 'draft',
            },
        );
    }
}
