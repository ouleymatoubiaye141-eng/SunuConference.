<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\User;
use App\Notifications\ReviewerCredentialsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReviewerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_chair_can_create_reviewer_for_own_validated_conference(): void
    {
        Notification::fake();

        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);

        $response = $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/reviewers", [
                'name' => 'Aminata Reviewer',
                'email' => 'aminata.reviewer@example.test',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', "Reviewer cr\u{00E9}\u{00E9} avec succ\u{00E8}s.")
            ->assertJsonPath('data.user.email', 'aminata.reviewer@example.test')
            ->assertJsonPath('data.user.role', User::ROLE_REVIEWER)
            ->assertJsonPath('data.user.must_change_password', true)
            ->assertJsonMissingPath('data.temporary_password');

        $reviewer = User::where('email', 'aminata.reviewer@example.test')->firstOrFail();

        Notification::assertSentTo(
            $reviewer,
            ReviewerCredentialsNotification::class,
            function (ReviewerCredentialsNotification $notification) use ($reviewer): bool {
                return $notification->reviewer->is($reviewer)
                    && Hash::check($notification->temporaryPassword, $reviewer->password);
            },
        );
        $this->assertDatabaseHas('conference_reviewers', [
            'conference_id' => $conference->id,
            'user_id' => $reviewer->id,
        ]);
    }

    public function test_chair_cannot_create_reviewer_in_pending_conference(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_PENDING);

        $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/reviewers", [
                'name' => 'Pending Reviewer',
                'email' => 'pending.reviewer@example.test',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'pending.reviewer@example.test',
        ]);
    }

    public function test_chair_cannot_create_reviewer_in_another_chairs_conference(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED);
        [, , $otherToken] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED);

        $this
            ->withToken($otherToken)
            ->postJson("/api/chair/conferences/{$conference->id}/reviewers", [
                'name' => 'Forbidden Reviewer',
                'email' => 'forbidden.reviewer@example.test',
            ])
            ->assertForbidden();
    }

    public function test_chair_can_list_reviewers_for_own_conference(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $reviewer = $this->reviewerForConference($conference, name: 'Binta Reviewer');

        $this
            ->withToken($token)
            ->getJson("/api/chair/conferences/{$conference->id}/reviewers")
            ->assertOk()
            ->assertJsonPath('message', "Reviewers r\u{00E9}cup\u{00E9}r\u{00E9}s avec succ\u{00E8}s.")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reviewer->id)
            ->assertJsonPath('data.0.name', 'Binta Reviewer');
    }

    public function test_chair_cannot_list_reviewers_from_another_chairs_conference(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED);
        [, , $otherToken] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED);

        $this
            ->withToken($otherToken)
            ->getJson("/api/chair/conferences/{$conference->id}/reviewers")
            ->assertForbidden();
    }

    public function test_chair_can_resend_reviewer_credentials_for_own_conference(): void
    {
        Notification::fake();

        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $reviewer = $this->reviewerForConference($conference);
        $oldPassword = $reviewer->password;

        $response = $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/reviewers/{$reviewer->id}/resend-credentials");

        $response
            ->assertOk()
            ->assertJsonPath('message', "Nouveaux identifiants g\u{00E9}n\u{00E9}r\u{00E9}s avec succ\u{00E8}s.")
            ->assertJsonMissingPath('data.temporary_password');

        $reviewer->refresh();

        $this->assertNotSame($oldPassword, $reviewer->password);
        $this->assertTrue($reviewer->must_change_password);
        Notification::assertSentTo(
            $reviewer,
            ReviewerCredentialsNotification::class,
            function (ReviewerCredentialsNotification $notification) use ($reviewer): bool {
                return $notification->reviewer->is($reviewer)
                    && Hash::check($notification->temporaryPassword, $reviewer->password);
            },
        );
    }

    public function test_chair_cannot_resend_credentials_for_reviewer_from_another_chairs_conference(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED);
        [, , $otherToken] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED);
        $reviewer = $this->reviewerForConference($conference);

        $this
            ->withToken($otherToken)
            ->postJson("/api/chair/conferences/{$conference->id}/reviewers/{$reviewer->id}/resend-credentials")
            ->assertForbidden();
    }

    public function test_chair_cannot_resend_credentials_for_reviewer_outside_conference(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $reviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);

        $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/reviewers/{$reviewer->id}/resend-credentials")
            ->assertNotFound();
    }

    public function test_chair_can_remove_reviewer_and_topic_assignments_from_conference(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $reviewer = $this->reviewerForConference($conference);
        $topic = $conference->topics()->create([
            'name' => 'Topic assigne',
            'description' => null,
        ]);

        DB::table('reviewer_topic')->insert([
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->withToken($token)
            ->deleteJson("/api/chair/conferences/{$conference->id}/reviewers/{$reviewer->id}")
            ->assertOk()
            ->assertJsonPath('message', "Reviewer retir\u{00E9} de la conf\u{00E9}rence avec succ\u{00E8}s.");

        $this->assertDatabaseMissing('conference_reviewers', [
            'conference_id' => $conference->id,
            'user_id' => $reviewer->id,
        ]);
        $this->assertDatabaseMissing('reviewer_topic', [
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $reviewer->id,
        ]);
    }

    public function test_chair_cannot_remove_reviewer_from_another_chairs_conference(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED);
        [, , $otherToken] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED);
        $reviewer = $this->reviewerForConference($conference);

        $this
            ->withToken($otherToken)
            ->deleteJson("/api/chair/conferences/{$conference->id}/reviewers/{$reviewer->id}")
            ->assertForbidden();
    }

    public function test_chair_cannot_remove_reviewer_outside_conference(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);
        $reviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);

        $this
            ->withToken($token)
            ->deleteJson("/api/chair/conferences/{$conference->id}/reviewers/{$reviewer->id}")
            ->assertNotFound();
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
            'revision_deadline' => '2026-09-10',
            'camera_ready_deadline' => '2026-09-20',
            'status' => $status,
            'program_published' => false,
        ]);

        $token = $chair->createToken('api-token')->plainTextToken;

        return [$chair, $conference, $token];
    }
}
