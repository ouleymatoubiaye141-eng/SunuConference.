<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReviewerAssignedTopicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_can_list_their_assigned_topics_across_conferences(): void
    {
        $reviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);

        [, $conferenceA] = $this->conferenceForChair(email: 'chair-a@example.test', name: 'Conference A');
        [, $conferenceB] = $this->conferenceForChair(email: 'chair-b@example.test', name: 'Conference B');
        $topicA = $this->topicForConference($conferenceA, 'Artificial Intelligence');
        $topicB = $this->topicForConference($conferenceB, 'Distributed Systems');

        $this->assignReviewerToTopic($conferenceA, $topicA, $reviewer);
        $this->assignReviewerToTopic($conferenceB, $topicB, $reviewer);

        $this
            ->withToken($reviewer->createToken('api-token')->plainTextToken)
            ->getJson('/api/reviewer/topics')
            ->assertOk()
            ->assertJsonPath('message', "Topics assign\u{00E9}s r\u{00E9}cup\u{00E9}r\u{00E9}s avec succ\u{00E8}s.")
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Artificial Intelligence')
            ->assertJsonPath('data.0.conference.name', 'Conference A')
            ->assertJsonPath('data.1.name', 'Distributed Systems')
            ->assertJsonPath('data.1.conference.name', 'Conference B');
    }

    public function test_reviewer_topics_do_not_include_assignments_from_another_reviewer(): void
    {
        $reviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);
        $otherReviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);

        [, $conference] = $this->conferenceForChair();
        $ownTopic = $this->topicForConference($conference, 'Own Topic');
        $otherTopic = $this->topicForConference($conference, 'Other Topic');

        $this->assignReviewerToTopic($conference, $ownTopic, $reviewer);
        $this->assignReviewerToTopic($conference, $otherTopic, $otherReviewer);

        $this
            ->withToken($reviewer->createToken('api-token')->plainTextToken)
            ->getJson('/api/reviewer/topics')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Own Topic');
    }

    public function test_reviewer_without_assigned_topics_gets_empty_list(): void
    {
        $reviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);

        $this
            ->withToken($reviewer->createToken('api-token')->plainTextToken)
            ->getJson('/api/reviewer/topics')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_chair_cannot_list_reviewer_assigned_topics(): void
    {
        [$chair] = $this->conferenceForChair();

        $this
            ->withToken($chair->createToken('api-token')->plainTextToken)
            ->getJson('/api/reviewer/topics')
            ->assertForbidden();
    }

    private function assignReviewerToTopic(Conference $conference, $topic, User $reviewer): void
    {
        $conference->reviewers()->attach($reviewer->id, [
            'invited_at' => now(),
        ]);

        DB::table('reviewer_topic')->insert([
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function topicForConference(Conference $conference, string $name)
    {
        return $conference->topics()->create([
            'name' => $name,
            'description' => "Description for {$name}.",
        ]);
    }

    private function conferenceForChair(
        string $email = 'chair@example.test',
        string $name = 'SunuConference 2026',
    ): array {
        $chair = User::factory()->create([
            'email' => $email,
            'role' => User::ROLE_CHAIR,
        ]);

        $conference = Conference::create([
            'chair_id' => $chair->id,
            'name' => $name,
            'acronym' => fake()->unique()->bothify('SUNU??##'),
            'description' => 'Conference academique.',
            'call_for_papers' => 'Appel a communications.',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'submission_deadline' => '2026-08-01',
            'review_deadline' => '2026-09-01',
            'revision_deadline' => '2026-09-10',
            'camera_ready_deadline' => '2026-09-20',
            'status' => Conference::STATUS_VALIDATED,
            'program_published' => false,
        ]);

        return [$chair, $conference];
    }
}
