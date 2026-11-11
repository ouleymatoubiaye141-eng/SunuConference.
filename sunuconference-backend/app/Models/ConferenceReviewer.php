<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConferenceReviewer extends Model
{
    protected $fillable = [
        'conference_id',
        'user_id',
        'invited_at',
    ];

    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
