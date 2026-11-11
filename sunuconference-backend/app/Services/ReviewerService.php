<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Conference;
use App\Models\ConferenceReviewer;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\ReviewerAssignedToTopicNotification;
use App\Notifications\ReviewerCredentialsNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ReviewerService
{
    public function create(Conference $conference, array $data): array
    {
        $plainPassword = $this->generateTemporaryPassword();

        $reviewer = DB::transaction(function () use ($conference, $data, $plainPassword): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => User::ROLE_REVIEWER,
                'password' => Hash::make($plainPassword),
                'must_change_password' => true,
            ]);

            ConferenceReviewer::create([
                'conference_id' => $conference->id,
                'user_id' => $user->id,
                'invited_at' => now(),
            ]);

            return $user;
        });

        $reviewer->notify(new ReviewerCredentialsNotification($reviewer, $plainPassword));

        return ['user' => $reviewer];
    }

    public function listForConference(Conference $conference): Collection
    {
        return $conference->reviewers()->orderBy('name')->get();
    }

    public function resendCredentials(User $reviewer): void
    {
        if ($reviewer->role !== User::ROLE_REVIEWER) {
            throw new ApiException(
                'Seul un compte reviewer peut recevoir de nouveaux identifiants.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $plainPassword = $this->generateTemporaryPassword();

        $reviewer->forceFill([
            'password' => Hash::make($plainPassword),
            'must_change_password' => true,
        ])->save();

        $reviewer->notify(new ReviewerCredentialsNotification($reviewer, $plainPassword));
    }

    public function removeFromConference(Conference $conference, User $reviewer): void
    {
        DB::transaction(function () use ($conference, $reviewer): void {
            $topicIds = $conference->topics()->pluck('id');

            DB::table('reviewer_topic')
                ->where('reviewer_id', $reviewer->id)
                ->whereIn('topic_id', $topicIds)
                ->delete();

            ConferenceReviewer::where('conference_id', $conference->id)
                ->where('user_id', $reviewer->id)
                ->delete();
        });
    }

    public function assignToTopic(Topic $topic, User $reviewer): void
    {
        $this->ensureReviewerCanBeAssignedToTopic($topic, $reviewer);

        $alreadyAssigned = DB::table('reviewer_topic')
            ->where('topic_id', $topic->id)
            ->where('reviewer_id', $reviewer->id)
            ->exists();

        if ($alreadyAssigned) {
            return;
        }

        DB::table('reviewer_topic')->insert([
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $topic->loadMissing('conference');

        $reviewer->notify(new ReviewerAssignedToTopicNotification($topic));
    }

    public function unassignFromTopic(Topic $topic, User $reviewer): void
    {
        DB::table('reviewer_topic')
            ->where('topic_id', $topic->id)
            ->where('reviewer_id', $reviewer->id)
            ->delete();
    }

    public function listAssignedReviewers(Topic $topic): Collection
    {
        return $topic->reviewers()->orderBy('name')->get();
    }

    public function listAssignedTopicsForReviewer(User $reviewer): Collection
    {
        return $reviewer->assignedTopics()
            ->with('conference')
            ->orderBy('topics.name')
            ->get();
    }

    private function ensureReviewerCanBeAssignedToTopic(Topic $topic, User $reviewer): void
    {
        if ($reviewer->role !== User::ROLE_REVIEWER) {
            throw new ApiException(
                "Seul un compte reviewer peut \u{00EA}tre assign\u{00E9} \u{00E0} un topic.",
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $isInConferencePool = ConferenceReviewer::where('conference_id', $topic->conference_id)
            ->where('user_id', $reviewer->id)
            ->exists();

        if (! $isInConferencePool) {
            throw new ApiException(
                "Ce relecteur doit d'abord \u{00EA}tre ajout\u{00E9} \u{00E0} la conf\u{00E9}rence avant d'\u{00EA}tre assign\u{00E9} \u{00E0} un topic.",
                Response::HTTP_CONFLICT,
            );
        }
    }

    private function generateTemporaryPassword(): string
    {
        return Str::random(32);
    }
}
