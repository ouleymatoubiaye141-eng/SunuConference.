<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\Review;
use App\Models\ReviewerSubmission;
use App\Models\Submission;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewerEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_can_list_assigned_submissions_with_review_status(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $conference = $this->createConference();
        $topic = $this->createTopic($conference);
        $token = $reviewer->createToken('api-token')->plainTextToken;

        // 1. Not started
        $sub1 = $this->createSubmission($conference, $topic);
        ReviewerSubmission::create(['submission_id' => $sub1->id, 'reviewer_id' => $reviewer->id, 'assigned_at' => now()->subDays(3)]);

        // 2. Draft
        $sub2 = $this->createSubmission($conference, $topic);
        $rs2 = ReviewerSubmission::create(['submission_id' => $sub2->id, 'reviewer_id' => $reviewer->id, 'assigned_at' => now()->subDays(2)]);
        Review::create(['reviewer_submission_id' => $rs2->id, 'public_comment' => 'Draft comment']);

        // 3. Submitted
        $sub3 = $this->createSubmission($conference, $topic);
        $rs3 = ReviewerSubmission::create(['submission_id' => $sub3->id, 'reviewer_id' => $reviewer->id, 'assigned_at' => now()->subDays(1)]);
        Review::create([
            'reviewer_submission_id' => $rs3->id,
            'originality_score' => 5,
            'relevance_score' => 5,
            'presentation_score' => 5,
            'overall_score' => 5,
            'public_comment' => 'Perfect',
            'recommendation' => 'accept',
            'submitted_at' => now()
        ]);

        // 4. Conflict
        $sub4 = $this->createSubmission($conference, $topic);
        $rs4 = ReviewerSubmission::create(['submission_id' => $sub4->id, 'reviewer_id' => $reviewer->id, 'assigned_at' => now()]);
        Review::create([
            'reviewer_submission_id' => $rs4->id,
            'conflict_of_interest' => true,
            'conflict_reason' => 'Laboratory conflict'
        ]);

        $this->withToken($token)
            ->getJson('/api/reviewer/submissions')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            // Descending order by assigned_at
            ->assertJsonPath('data.0.review.status', 'conflict_declared')
            ->assertJsonPath('data.1.review.status', 'submitted')
            ->assertJsonPath('data.2.review.status', 'draft')
            ->assertJsonPath('data.3.review', null);
    }

    public function test_reviewer_can_save_partial_draft(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs = $this->createAssignment($reviewer);
        $token = $reviewer->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/reviewer/submissions/{$rs->id}/review", [
                'public_comment' => 'Brouillon en cours...',
                'originality_score' => 3
            ])
            ->assertOk();

        $this->assertDatabaseHas('reviews', [
            'reviewer_submission_id' => $rs->id,
            'public_comment' => 'Brouillon en cours...',
            'originality_score' => 3,
            'submitted_at' => null
        ]);
    }

    public function test_reviewer_can_update_draft_multiple_times(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs = $this->createAssignment($reviewer);
        $token = $reviewer->createToken('api-token')->plainTextToken;

        $this->withToken($token)->putJson("/api/reviewer/submissions/{$rs->id}/review", ['public_comment' => 'V1']);
        $this->withToken($token)->putJson("/api/reviewer/submissions/{$rs->id}/review", ['public_comment' => 'V2']);

        $this->assertDatabaseHas('reviews', [
            'reviewer_submission_id' => $rs->id,
            'public_comment' => 'V2'
        ]);
        $this->assertEquals(1, Review::where('reviewer_submission_id', $rs->id)->count());
    }

    public function test_reviewer_cannot_access_other_reviewer_assignment(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $otherReviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs = $this->createAssignment($otherReviewer);
        $token = $reviewer->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/reviewer/submissions/{$rs->id}/review", ['public_comment' => 'Hacked'])
            ->assertStatus(404);
    }

    public function test_reviewer_can_submit_complete_report(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs = $this->createAssignment($reviewer);
        $token = $reviewer->createToken('api-token')->plainTextToken;

        $payload = [
            'originality_score' => 4,
            'relevance_score' => 4,
            'presentation_score' => 4,
            'overall_score' => 4,
            'public_comment' => 'Très bon travail.',
            'recommendation' => 'accept'
        ];

        $this->withToken($token)
            ->postJson("/api/reviewer/submissions/{$rs->id}/review/submit", $payload)
            ->assertOk();

        $this->assertDatabaseHas('reviews', array_merge($payload, [
            'reviewer_submission_id' => $rs->id
        ]));
        $this->assertNotNull(Review::where('reviewer_submission_id', $rs->id)->first()->submitted_at);
        $this->assertEquals('submitted', $rs->fresh()->review->status);
    }

    public function test_reviewer_cannot_submit_incomplete_report(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs = $this->createAssignment($reviewer);
        $token = $reviewer->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/reviewer/submissions/{$rs->id}/review/submit", [
                'originality_score' => 4
                // missing other fields
            ])
            ->assertStatus(422);
    }

    public function test_reviewer_cannot_modify_submitted_report(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs = $this->createAssignment($reviewer);
        Review::create([
            'reviewer_submission_id' => $rs->id,
            'originality_score' => 5, 'relevance_score' => 5, 'presentation_score' => 5, 'overall_score' => 5,
            'public_comment' => 'Perfect', 'recommendation' => 'accept', 'submitted_at' => now()
        ]);
        $token = $reviewer->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/reviewer/submissions/{$rs->id}/review", ['public_comment' => 'Edit attempt'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Impossible de modifier un rapport déjà soumis.');
    }

    public function test_reviewer_can_declare_conflict_and_it_preserves_assignment(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs = $this->createAssignment($reviewer);
        $token = $reviewer->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/reviewer/submissions/{$rs->id}/conflict", [
                'reason' => 'Conflit détecté.'
            ])
            ->assertOk();

        $this->assertDatabaseHas('reviews', [
            'reviewer_submission_id' => $rs->id,
            'conflict_of_interest' => true,
            'conflict_reason' => 'Conflit détecté.'
        ]);
        $this->assertDatabaseHas('reviewer_submission', ['id' => $rs->id]);
    }

    public function test_reviewer_cannot_declare_conflict_if_already_submitted(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs = $this->createAssignment($reviewer);
        Review::create([
            'reviewer_submission_id' => $rs->id,
            'originality_score' => 5, 'relevance_score' => 5, 'presentation_score' => 5, 'overall_score' => 5,
            'public_comment' => 'Perfect', 'recommendation' => 'accept', 'submitted_at' => now()
        ]);
        $token = $reviewer->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/reviewer/submissions/{$rs->id}/conflict", ['reason' => 'Too late'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Impossible de déclarer un conflit pour un rapport déjà soumis.');
    }

    public function test_reviewer_cannot_modify_review_after_conflict_declared(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs = $this->createAssignment($reviewer);
        Review::create([
            'reviewer_submission_id' => $rs->id,
            'conflict_of_interest' => true,
            'conflict_reason' => 'Conflict'
        ]);
        $token = $reviewer->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/reviewer/submissions/{$rs->id}/review", ['public_comment' => 'Try to edit'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Impossible de modifier un rapport après avoir déclaré un conflit d’intérêt.');
    }

    public function test_non_reviewer_cannot_access_reviewer_routes(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_AUTHOR]);
        $token = $author->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/reviewer/submissions')
            ->assertForbidden();
    }

    private function createConference(): Conference
    {
        $chair = User::factory()->create(['role' => User::ROLE_CHAIR]);
        return Conference::create([
            'chair_id' => $chair->id,
            'name' => 'Conf',
            'acronym' => fake()->unique()->lexify('??????'),
            'description' => 'Desc',
            'call_for_papers' => 'CFP',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-02',
            'submission_deadline' => '2025-12-01',
            'review_deadline' => '2025-12-15',
            'camera_ready_deadline' => '2025-12-20',
            'status' => Conference::STATUS_VALIDATED,
            'program_published' => false
        ]);
    }

    private function createTopic(Conference $conference): Topic
    {
        return $conference->topics()->create(['name' => 'Topic', 'description' => 'Desc']);
    }

    private function createSubmission(Conference $conference, Topic $topic): Submission
    {
        $author = User::factory()->create(['role' => User::ROLE_AUTHOR]);
        return Submission::create([
            'conference_id' => $conference->id,
            'topic_id' => $topic->id,
            'author_id' => $author->id,
            'title' => 'Title',
            'abstract' => 'Abstract',
            'keywords' => ['k'],
            'initial_file_path' => 'file.pdf',
            'status' => Submission::STATUS_SUBMITTED,
            'submitted_at' => now()
        ]);
    }

    private function createAssignment(User $reviewer): ReviewerSubmission
    {
        $conf = $this->createConference();
        $topic = $this->createTopic($conf);
        $sub = $this->createSubmission($conf, $topic);
        return ReviewerSubmission::create([
            'submission_id' => $sub->id,
            'reviewer_id' => $reviewer->id,
            'assigned_at' => now()
        ]);
    }
}
