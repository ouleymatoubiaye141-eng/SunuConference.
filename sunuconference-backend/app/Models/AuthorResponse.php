<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorResponse extends Model
{
    use HasFactory;

    public const TYPE_PRE_DECISION = 'pre_decision';
    
    public const TYPE_REVISION_RESPONSE = 'revision_response';

    protected $fillable = [
        'submission_id',
        'author_id',
        'type',
        'content',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
