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

class ReviewSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_chair_can_view_review_summary_with_correct_averages(): void
    {
        [$chair, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);

        // Review 1: 5 everywhere
        $r1 = $this->createSubmittedReview($submission, [
            'originality_score' => 5, 'relevance_score' => 5, 'presentation_score' => 5, 'overall_score' => 5,
            'recommendation' => 'accept'
        ]);

        // Review 2: 3 everywhere
        $r2 = $this->createSubmittedReview($submission, [
            'originality_score' => 3, 'relevance_score' => 3, 'presentation_score' => 3, 'overall_score' => 3,
            'recommendation' => 'minor_revision'
        ]);

        // Review 3: mixed scores
        $r3 = $this->createSubmittedReview($submission, [
            'originality_score' => 4, 'relevance_score' => 2, 'presentation_score' => 5, 'overall_score' => 4,
            'recommendation' => 'accept'
        ]);

        $response = $this->withToken($token)
            ->getJson("/api/chair/conferences/{$conference->id}/submissions/{$submission->id}/reviews-summary")
            ->assertOk();

        $response->assertJsonPath('data.submission_id', $submission->id)
            ->assertJsonCount(3, 'data.submitted_reviews');

        $this->assertEquals(4.0, $response->json('data.average_scores.originality'));
        $this->assertEquals(3.3, $response->json('data.average_scores.relevance'));
        $this->assertEquals(4.3, $response->json('data.average_scores.presentation'));
        $this->assertEquals(4.0, $response->json('data.average_scores.overall'));

        $response->assertJsonPath('data.recommendations_distribution.accept', 2)
            ->assertJsonPath('data.recommendations_distribution.minor_revision', 1);

        // Vérification anti-récursion
        $submittedReviewData = $response->json('data.submitted_reviews.0');
        $this->assertArrayHasKey('reviewer_submission', $submittedReviewData);
        $this->assertArrayNotHasKey('review', $submittedReviewData['reviewer_submission']);
    }

    public function test_drafts_and_conflicts_are_excluded_from_averages_but_listed_separately(): void
    {
        [, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);

        // One submitted
        $this->createSubmittedReview($submission, [
            'originality_score' => 4, 'relevance_score' => 4, 'presentation_score' => 4, 'overall_score' => 4,
            'recommendation' => 'accept'
        ]);

        // One conflict
        $reviewerConflict = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rsConflict = ReviewerSubmission::create([
            'submission_id' => $submission->id, 'reviewer_id' => $reviewerConflict->id, 'assigned_at' => now()
        ]);
        Review::create([
            'reviewer_submission_id' => $rsConflict->id, 'conflict_of_interest' => true, 'conflict_reason' => 'Labs'
        ]);

        // One draft
        $reviewerDraft = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        ReviewerSubmission::create([
            'submission_id' => $submission->id, 'reviewer_id' => $reviewerDraft->id, 'assigned_at' => now()
        ]);

        $response = $this->withToken($token)
            ->getJson("/api/chair/conferences/{$conference->id}/submissions/{$submission->id}/reviews-summary")
            ->assertOk();

        $response->assertJsonCount(1, 'data.submitted_reviews')
            ->assertJsonCount(1, 'data.conflicts')
            ->assertJsonCount(1, 'data.pending_assignments');

        $this->assertEquals(4.0, $response->json('data.average_scores.overall'));
    }

    public function test_no_reviews_submitted_returns_null_averages(): void
    {
        [, $conference, $token] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);

        $this->withToken($token)
            ->getJson("/api/chair/conferences/{$conference->id}/submissions/{$submission->id}/reviews-summary")
            ->assertOk()
            ->assertJsonPath('data.average_scores.overall', null)
            ->assertJsonCount(0, 'data.recommendations_distribution');
    }

    public function test_chair_of_another_conference_cannot_view_summary(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test');
        [, , $otherToken] = $this->conferenceForChair(email: 'other@example.test');
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionForConferenceTopic($conference, $topic);

        $this->withToken($otherToken)
            ->getJson("/api/chair/conferences/{$conference->id}/submissions/{$submission->id}/reviews-summary")
            ->assertForbidden();
    }

    public function test_submission_mismatch_conference_returns_404(): void
    {
        [, $conference, $token] = $this->conferenceForChair();
        [, $otherConference] = $this->conferenceForChair(email: 'other@example.test');
        $topic = $this->topicForConference($otherConference);
        $submission = $this->submissionForConferenceTopic($otherConference, $topic);

        $this->withToken($token)
            ->getJson("/api/chair/conferences/{$conference->id}/submissions/{$submission->id}/reviews-summary")
            ->assertNotFound();
    }

    private function createSubmittedReview(Submission $submission, array $scores): Review
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs = ReviewerSubmission::create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'assigned_at' => now()
        ]);

        return Review::create(array_merge($scores, [
            'reviewer_submission_id' => $rs->id,
            'public_comment' => 'Commentaire public',
            'private_comment' => 'Commentaire privé',
            'submitted_at' => now()
        ]));
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
}
