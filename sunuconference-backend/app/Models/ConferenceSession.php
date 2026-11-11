<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConferenceSession extends Model
{
    use HasFactory;

    protected $table = 'conference_sessions';

    protected $fillable = [
        'conference_id',
        'title',
        'room',
        'session_date',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'conference_session_id');
    }
}
