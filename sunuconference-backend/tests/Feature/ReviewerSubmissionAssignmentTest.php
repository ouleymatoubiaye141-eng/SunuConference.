<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\Review;
use App\Models\ReviewerSubmission;
use App\Models\Submission;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\ReviewerAssignedToSubmissionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReviewerSubmissionAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_chair_can_assign_competent_reviewer_to_submission_and_transition_to_under_review(): void
    {
        Notification::fake();

        [$chair, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);
        $reviewer = $this->reviewerForConference($conference);
        $this->assignReviewerToTopic($topic, $reviewer);

        $this
            ->withToken($token)
            ->postJson($this->submissionReviewersUrl($conference, $submission), [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Reviewer assigné à la soumission avec succès.');

        $this->assertDatabaseHas('reviewer_submission', [
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'assigned_by' => $chair->id,
        ]);
        $this->assertNotNull(ReviewerSubmission::firstOrFail()->assigned_at);
        $this->assertSame(Submission::STATUS_UNDER_REVIEW, $submission->fresh()->status);
        Notification::assertSentTo(
            $reviewer,
            ReviewerAssignedToSubmissionNotification::class,
            fn (ReviewerAssignedToSubmissionNotification $notification, array $channels): bool => $channels === ['mail', 'database'],
        );
    }

    public function test_chair_cannot_assign_reviewer_not_competent_for_submission_topic(): void
    {
        [, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);
        $reviewer = $this->reviewerForConference($conference);

        $this
            ->withToken($token)
            ->postJson($this->submissionReviewersUrl($conference, $submission), [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', "Ce reviewer doit d'abord être assigné au topic de cette soumission avant de recevoir une relecture.");

        $this->assertDatabaseMissing('reviewer_submission', [
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
        ]);
        $this->assertSame(Submission::STATUS_SUBMITTED, $submission->fresh()->status);
    }

    public function test_assigning_same_reviewer_to_submission_twice_is_idempotent(): void
    {
        Notification::fake();

        [, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);
        $reviewer = $this->reviewerForConference($conference);
        $this->assignReviewerToTopic($topic, $reviewer);

        $payload = ['reviewer_id' => $reviewer->id];

        $this
            ->withToken($token)
            ->postJson($this->submissionReviewersUrl($conference, $submission), $payload)
            ->assertOk();

        $this
            ->withToken($token)
            ->postJson($this->submissionReviewersUrl($conference, $submission), $payload)
            ->assertOk();

        $this->assertSame(1, DB::table('reviewer_submission')
            ->where('submission_id', $submission->id)
            ->where('reviewer_id', $reviewer->id)
            ->count());
        $this->assertCount(
            1,
            Notification::sent($reviewer, ReviewerAssignedToSubmissionNotification::class),
        );
    }

    public function test_reviewer_is_notified_when_assigned_to_submission(): void
    {
        Notification::fake();

        [, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic, [
            'title' => 'Article à relire',
        ]);
        $reviewer = $this->reviewerForConference($conference);
        $this->assignReviewerToTopic($topic, $reviewer);

        $this
            ->withToken($token)
            ->postJson($this->submissionReviewersUrl($conference, $submission), [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertOk();

        Notification::assertSentTo(
            $reviewer,
            ReviewerAssignedToSubmissionNotification::class,
            function (ReviewerAssignedToSubmissionNotification $notification, array $channels) use ($submission): bool {
                $payload = $notification->toArray($submission->author);

                return $channels === ['mail', 'database']
                    && $payload['event'] === 'submission.reviewer_assigned'
                    && $payload['entity_type'] === 'submission'
                    && $payload['entity_id'] === $submission->id
                    && str_contains($payload['message'], 'Article à relire');
            },
        );
    }

    public function test_reviewer_is_not_notified_again_when_submission_assignment_already_exists(): void
    {
        Notification::fake();

        [$chair, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);
        $reviewer = $this->reviewerForConference($conference);
        $this->assignReviewerToTopic($topic, $reviewer);

        ReviewerSubmission::create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'assigned_by' => $chair->id,
            'assigned_at' => now(),
        ]);

        $this
            ->withToken($token)
            ->postJson($this->submissionReviewersUrl($conference, $submission), [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertOk();

        Notification::assertNothingSent();
    }

    #[DataProvider('finalStatusesProvider')]
    public function test_assignment_is_refused_when_submission_has_final_status(string $status): void
    {
        [, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic, [
            'status' => $status,
        ]);
        $reviewer = $this->reviewerForConference($conference);
        $this->assignReviewerToTopic($topic, $reviewer);

        $this
            ->withToken($token)
            ->postJson($this->submissionReviewersUrl($conference, $submission), [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Cette soumission ne peut plus recevoir de reviewer.');

        $this->assertDatabaseMissing('reviewer_submission', [
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
        ]);
    }

    public function test_chair_from_another_conference_cannot_assign_reviewer_to_submission(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test');
        [, , $otherToken] = $this->conferenceForChair(email: 'other@example.test');
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);
        $reviewer = $this->reviewerForConference($conference);
        $this->assignReviewerToTopic($topic, $reviewer);

        $this
            ->withToken($otherToken)
            ->postJson($this->submissionReviewersUrl($conference, $submission), [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertForbidden();
    }

    public function test_submission_mismatching_conference_url_returns_not_found(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test');
        [, $otherConference, $otherToken] = $this->conferenceForChair(email: 'other@example.test');
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);
        $reviewer = $this->reviewerForConference($conference);
        $this->assignReviewerToTopic($topic, $reviewer);

        $this
            ->withToken($otherToken)
            ->postJson($this->submissionReviewersUrl($otherConference, $submission), [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertNotFound();
    }

    public function test_chair_can_list_reviewer_assignments_for_submission(): void
    {
        [, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);
        $reviewer = $this->reviewerForConference($conference, name: 'Assigned Reviewer');

        $reviewerSubmission = ReviewerSubmission::create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'assigned_at' => now(),
        ]);

        Review::create([
            'reviewer_submission_id' => $reviewerSubmission->id,
            'conflict_of_interest' => true,
            'conflict_reason' => 'Même laboratoire.',
        ]);

        $this
            ->withToken($token)
            ->getJson($this->submissionReviewersUrl($conference, $submission))
            ->assertOk()
            ->assertJsonPath('message', 'Assignations de reviewers récupérées avec succès.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reviewerSubmission->id)
            ->assertJsonPath('data.0.reviewer.id', $reviewer->id)
            ->assertJsonPath('data.0.reviewer.name', 'Assigned Reviewer')
            ->assertJsonPath('data.0.review.status', 'conflict_declared')
            ->assertJsonPath('data.0.review.conflict_of_interest', true)
            ->assertJsonPath('data.0.review.conflict_reason', 'Même laboratoire.');
    }

    public function test_chair_can_remove_reviewer_from_submission_when_review_is_not_submitted(): void
    {
        [, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);
        $reviewer = $this->reviewerForConference($conference);

        $reviewerSubmission = ReviewerSubmission::create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'assigned_at' => now(),
        ]);

        Review::create([
            'reviewer_submission_id' => $reviewerSubmission->id,
            'public_comment' => 'Brouillon en cours.',
        ]);

        $this
            ->withToken($token)
            ->deleteJson($this->submissionReviewersUrl($conference, $submission), [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Reviewer retiré de la soumission avec succès.');

        $this->assertDatabaseMissing('reviewer_submission', [
            'id' => $reviewerSubmission->id,
        ]);
        $this->assertDatabaseMissing('reviews', [
            'reviewer_submission_id' => $reviewerSubmission->id,
        ]);
    }

    public function test_chair_cannot_remove_reviewer_from_submission_when_conflict_is_declared(): void
    {
        [, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);
        $reviewer = $this->reviewerForConference($conference);

        $reviewerSubmission = ReviewerSubmission::create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'assigned_at' => now(),
        ]);

        Review::create([
            'reviewer_submission_id' => $reviewerSubmission->id,
            'conflict_of_interest' => true,
            'conflict_reason' => 'Même laboratoire.',
        ]);

        $this
            ->withToken($token)
            ->deleteJson($this->submissionReviewersUrl($conference, $submission), [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Impossible de retirer un reviewer ayant déclaré un conflit d’intérêt.');

        $this->assertDatabaseHas('reviewer_submission', [
            'id' => $reviewerSubmission->id,
        ]);
        $this->assertDatabaseHas('reviews', [
            'reviewer_submission_id' => $reviewerSubmission->id,
            'conflict_of_interest' => true,
            'conflict_reason' => 'Même laboratoire.',
        ]);
    }

    public function test_chair_cannot_remove_reviewer_from_submission_when_review_is_submitted(): void
    {
        [, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);
        $reviewer = $this->reviewerForConference($conference);

        $reviewerSubmission = ReviewerSubmission::create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'assigned_at' => now(),
        ]);

        Review::create([
            'reviewer_submission_id' => $reviewerSubmission->id,
            'originality_score' => 4,
            'relevance_score' => 4,
            'presentation_score' => 4,
            'overall_score' => 4,
            'public_comment' => 'Bon article.',
            'recommendation' => Review::RECOMMENDATION_ACCEPT,
            'submitted_at' => now(),
        ]);

        $this
            ->withToken($token)
            ->deleteJson($this->submissionReviewersUrl($conference, $submission), [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Impossible de retirer un reviewer dont la review a déjà été soumise.');

        $this->assertDatabaseHas('reviewer_submission', [
            'id' => $reviewerSubmission->id,
        ]);
    }

    public function test_removing_reviewer_from_submission_is_idempotent_when_not_assigned(): void
    {
        [, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);
        $reviewer = $this->reviewerForConference($conference);

        $this
            ->withToken($token)
            ->deleteJson($this->submissionReviewersUrl($conference, $submission), [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Reviewer retiré de la soumission avec succès.');

        $this->assertDatabaseMissing('reviewer_submission', [
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
        ]);
    }

    private function conferenceForChair(
        string $email = 'chair@example.test',
        string $status = Conference::STATUS_VALIDATED,
    ): array {
        $chair = User::factory()->create([
            'email' => $email,
            'role' => User::ROLE_CHAIR,
        ]);

        $conference = Conference::create([
            'chair_id' => $chair->id,
            'name' => 'SunuConference 2026',
            'acronym' => fake()->unique()->bothify('SUNU??##'),
            'description' => 'Conférence académique.',
            'call_for_papers' => 'Appel à communications.',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'submission_deadline' => '2026-08-01',
            'review_deadline' => '2026-09-01',
            'revision_deadline' => '2026-09-10',
            'camera_ready_deadline' => '2026-09-20',
            'status' => $status,
            'program_published' => false,
        ]);

        $token = $chair->createToken('api-token')->plainTextToken;

        return [$chair, $conference, $token];
    }

    private function topicForConference(Conference $conference): Topic
    {
        return $conference->topics()->create([
            'name' => fake()->unique()->words(3, true),
            'description' => null,
        ]);
    }

    private function submissionForConferenceTopic(Conference $conference, Topic $topic, array $overrides = []): Submission
    {
        $author = User::factory()->create([
            'role' => User::ROLE_AUTHOR,
        ]);

        return Submission::create(array_merge([
            'conference_id' => $conference->id,
            'topic_id' => $topic->id,
            'author_id' => $author->id,
            'title' => 'Article SunuConference',
            'abstract' => 'Résumé détaillé de l article.',
            'keywords' => ['laravel', 'conference'],
            'initial_file_path' => "submissions/{$conference->id}/initial.pdf",
            'status' => Submission::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ], $overrides));
    }

    private function reviewerForConference(Conference $conference, string $name = 'Reviewer'): User
    {
        $reviewer = User::factory()->create([
            'name' => $name,
            'role' => User::ROLE_REVIEWER,
        ]);

        $conference->reviewers()->attach($reviewer->id, [
            'invited_at' => now(),
        ]);

        return $reviewer;
    }

    private function assignReviewerToTopic(Topic $topic, User $reviewer): void
    {
        DB::table('reviewer_topic')->insert([
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function submissionReviewersUrl(Conference $conference, Submission $submission): string
    {
        return "/api/chair/conferences/{$conference->id}/submissions/{$submission->id}/reviewers";
    }

    public static function finalStatusesProvider(): array
    {
        return [
            'accepted' => [Submission::STATUS_ACCEPTED],
            'rejected' => [Submission::STATUS_REJECTED],
            'camera_ready_submitted' => [Submission::STATUS_CAMERA_READY_SUBMITTED],
        ];
    }
}
