<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'conference_id',
        'name',
        'description',
    ];

    public function conference()
    {
        return $this->belongsTo(Conference::class);
    }

    public function reviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reviewer_topic', 'topic_id', 'reviewer_id')
            ->withTimestamps();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
