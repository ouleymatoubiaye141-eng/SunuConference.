<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decision extends Model
{
    use HasFactory;

    public const DECISION_ACCEPT = 'accept';

    public const DECISION_REJECT = 'reject';

    public const DECISION_REVISION_REQUIRED = 'revision_required';

    protected $fillable = [
        'submission_id',
        'chair_id',
        'decision',
        'comments',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function chair(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chair_id');
    }
}
