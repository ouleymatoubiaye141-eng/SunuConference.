<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\User;
use App\Notifications\ReviewerAssignedToTopicNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TopicReviewerAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_chair_can_assign_reviewer_from_conference_pool_to_topic(): void
    {
        Notification::fake();

        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);
        $reviewer = $this->reviewerForConference($conference);

        $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}/reviewers", [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('reviewer_topic', [
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
        ]);
        Notification::assertSentTo(
            $reviewer,
            ReviewerAssignedToTopicNotification::class,
            fn (ReviewerAssignedToTopicNotification $notification, array $channels): bool => $channels === ['database'],
        );
    }

    public function test_assigning_same_reviewer_to_topic_twice_is_idempotent(): void
    {
        Notification::fake();

        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);
        $reviewer = $this->reviewerForConference($conference);

        $payload = ['reviewer_id' => $reviewer->id];

        $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}/reviewers", $payload)
            ->assertOk();

        $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}/reviewers", $payload)
            ->assertOk();

        $this->assertSame(1, DB::table('reviewer_topic')
            ->where('topic_id', $topic->id)
            ->where('reviewer_id', $reviewer->id)
            ->count());
        $this->assertCount(
            1,
            Notification::sent($reviewer, ReviewerAssignedToTopicNotification::class),
        );
    }

    public function test_reviewer_is_notified_when_assigned_to_topic(): void
    {
        Notification::fake();

        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);
        $reviewer = $this->reviewerForConference($conference);

        $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}/reviewers", [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertOk();

        Notification::assertSentTo(
            $reviewer,
            ReviewerAssignedToTopicNotification::class,
            function (ReviewerAssignedToTopicNotification $notification, array $channels) use ($topic): bool {
                $payload = $notification->toArray($topic->reviewers()->firstOrFail());

                return $channels === ['database']
                    && $payload['event'] === 'topic.reviewer_assigned'
                    && $payload['entity_type'] === 'topic'
                    && $payload['entity_id'] === $topic->id
                    && str_contains($payload['message'], $topic->name);
            },
        );
    }

    public function test_reviewer_is_not_notified_again_when_topic_assignment_already_exists(): void
    {
        Notification::fake();

        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);
        $reviewer = $this->reviewerForConference($conference);

        DB::table('reviewer_topic')->insert([
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}/reviewers", [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertOk();

        Notification::assertNothingSent();
    }

    public function test_chair_can_list_reviewers_assigned_to_topic(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);
        $reviewer = $this->reviewerForConference($conference, name: 'Assigned Reviewer');

        DB::table('reviewer_topic')->insert([
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->withToken($token)
            ->getJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}/reviewers")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reviewer->id)
            ->assertJsonPath('data.0.name', 'Assigned Reviewer');
    }

    public function test_chair_cannot_list_assigned_reviewers_from_another_chairs_topic(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED);
        [, , $otherToken] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);

        $this
            ->withToken($otherToken)
            ->getJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}/reviewers")
            ->assertForbidden();
    }

    public function test_chair_cannot_list_assigned_reviewers_via_mismatched_conference_url(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED);
        [, $otherConference, $otherToken] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);

        $this
            ->withToken($otherToken)
            ->getJson("/api/chair/conferences/{$otherConference->id}/topics/{$topic->id}/reviewers")
            ->assertNotFound();
    }

    public function test_chair_cannot_assign_reviewer_outside_conference_pool_to_topic(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);
        $reviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);

        $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}/reviewers", [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', "Ce relecteur doit d'abord \u{00EA}tre ajout\u{00E9} \u{00E0} la conf\u{00E9}rence avant d'\u{00EA}tre assign\u{00E9} \u{00E0} un topic.");

        $this->assertDatabaseMissing('reviewer_topic', [
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
        ]);
    }

    public function test_chair_cannot_assign_non_reviewer_user_to_topic(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);
        $author = User::factory()->create([
            'role' => User::ROLE_AUTHOR,
        ]);

        $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}/reviewers", [
                'reviewer_id' => $author->id,
            ])
            ->assertUnprocessable();
    }

    public function test_chair_cannot_assign_reviewer_to_another_chairs_topic(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED);
        [, , $otherToken] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);
        $reviewer = $this->reviewerForConference($conference);

        $this
            ->withToken($otherToken)
            ->postJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}/reviewers", [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertForbidden();
    }

    public function test_chair_cannot_assign_reviewer_via_mismatched_conference_url(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED);
        [, $otherConference, $otherToken] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);
        $reviewer = $this->reviewerForConference($conference);

        $this
            ->withToken($otherToken)
            ->postJson("/api/chair/conferences/{$otherConference->id}/topics/{$topic->id}/reviewers", [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertNotFound();
    }

    public function test_chair_can_unassign_reviewer_from_topic(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);
        $reviewer = $this->reviewerForConference($conference);

        DB::table('reviewer_topic')->insert([
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->withToken($token)
            ->deleteJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}/reviewers", [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertOk();

        $this->assertDatabaseMissing('reviewer_topic', [
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
        ]);
    }

    public function test_unassigning_reviewer_not_assigned_to_topic_is_idempotent(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $topic = $this->topicForConference($conference);
        $reviewer = $this->reviewerForConference($conference);

        $this
            ->withToken($token)
            ->deleteJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}/reviewers", [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertOk();

        $this->assertDatabaseMissing('reviewer_topic', [
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
        ]);
    }

    private function topicForConference(Conference $conference)
    {
        return $conference->topics()->create([
            'name' => fake()->unique()->words(3, true),
            'description' => null,
        ]);
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
            'description' => 'Conference academique.',
            'call_for_papers' => 'Appel a communications.',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'submission_deadline' => '2026-08-01',
            'review_deadline' => '2026-09-01',
            'camera_ready_deadline' => '2026-09-20',
            'status' => $status,
            'program_published' => false,
        ]);

        $token = $chair->createToken('api-token')->plainTextToken;

        return [$chair, $conference, $token];
    }
}
