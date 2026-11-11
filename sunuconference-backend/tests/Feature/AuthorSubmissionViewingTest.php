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

class AuthorSubmissionViewingTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_list_only_their_submissions_ordered_by_newest(): void
    {
        $author = $this->author('author@example.test');
        $otherAuthor = $this->author('other@example.test');
        [$conference, $topic] = $this->conferenceAndTopic();
        [$otherConference, $otherTopic] = $this->conferenceAndTopic('Other Conf');

        $oldSubmission = $this->submissionFor($author, $conference, $topic, [
            'title' => 'Ancienne soumission',
        ]);
        $newSubmission = $this->submissionFor($author, $otherConference, $otherTopic, [
            'title' => 'Nouvelle soumission',
        ]);
        $this->submissionFor($otherAuthor, $conference, $topic, [
            'title' => 'Soumission autre auteur',
        ]);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->get('/api/author/submissions')
            ->assertOk()
            ->assertJsonPath('message', "Soumissions r\u{00E9}cup\u{00E9}r\u{00E9}es avec succ\u{00E8}s.")
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newSubmission->id)
            ->assertJsonPath('data.0.title', 'Nouvelle soumission')
            ->assertJsonPath('data.0.conference.name', 'Other Conf')
            ->assertJsonPath('data.0.topic.name', $otherTopic->name)
            ->assertJsonPath('data.1.id', $oldSubmission->id);
    }

    public function test_author_without_submissions_gets_empty_list(): void
    {
        $author = $this->author();

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->get('/api/author/submissions')
            ->assertOk()
            ->assertJsonPath('message', "Soumissions r\u{00E9}cup\u{00E9}r\u{00E9}es avec succ\u{00E8}s.")
            ->assertJsonCount(0, 'data')
            ->assertExactJson([
                'message' => "Soumissions r\u{00E9}cup\u{00E9}r\u{00E9}es avec succ\u{00E8}s.",
                'data' => [],
            ]);
    }

    public function test_author_can_view_their_submission_with_co_authors(): void
    {
        $author = $this->author();
        [$conference, $topic] = $this->conferenceAndTopic();
        $submission = $this->submissionFor($author, $conference, $topic, [
            'title' => 'Article avec coauteurs',
        ]);

        $submission->coAuthors()->create([
            'name' => 'Awa Coauthor',
            'email' => 'awa@example.test',
            'affiliation' => 'Universite de Dakar',
            'position' => 0,
        ]);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->get("/api/author/submissions/{$submission->id}")
            ->assertOk()
            ->assertJsonPath('message', "Soumission r\u{00E9}cup\u{00E9}r\u{00E9}e avec succ\u{00E8}s.")
            ->assertJsonPath('data.id', $submission->id)
            ->assertJsonPath('data.title', 'Article avec coauteurs')
            ->assertJsonPath('data.conference.id', $conference->id)
            ->assertJsonPath('data.topic.id', $topic->id)
            ->assertJsonCount(1, 'data.co_authors')
            ->assertJsonPath('data.co_authors.0.name', 'Awa Coauthor');
    }

    public function test_author_cannot_view_another_authors_submission(): void
    {
        $author = $this->author('author@example.test');
        $otherAuthor = $this->author('other@example.test');
        [$conference, $topic] = $this->conferenceAndTopic();
        $submission = $this->submissionFor($otherAuthor, $conference, $topic);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->get("/api/author/submissions/{$submission->id}")
            ->assertNotFound();
    }

    public function test_non_author_cannot_list_author_submissions(): void
    {
        $chair = User::factory()->create([
            'role' => User::ROLE_CHAIR,
        ]);
        $reviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($chair->createToken('api-token')->plainTextToken)
            ->get('/api/author/submissions')
            ->assertForbidden();

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($reviewer->createToken('api-token')->plainTextToken)
            ->get('/api/author/submissions')
            ->assertForbidden();
    }

    public function test_author_sees_public_comments_of_submitted_reviews(): void
    {
        $author = $this->author();
        [$conference, $topic] = $this->conferenceAndTopic();
        $submission = $this->submissionFor($author, $conference, $topic);

        // 1. Review soumise (doit être visible)
        $rs1 = $this->reviewerSubmissionFor($submission);
        Review::create([
            'reviewer_submission_id' => $rs1->id,
            'public_comment' => 'Commentaire public 1',
            'private_comment' => 'SECRET 1',
            'submitted_at' => now(),
            'conflict_of_interest' => false,
        ]);

        // 2. Autre review soumise (doit être visible)
        $rs2 = $this->reviewerSubmissionFor($submission);
        Review::create([
            'reviewer_submission_id' => $rs2->id,
            'public_comment' => 'Commentaire public 2',
            'private_comment' => 'SECRET 2',
            'submitted_at' => now(),
            'conflict_of_interest' => false,
        ]);

        // 3. Review en brouillon (ne doit PAS être visible)
        $rs3 = $this->reviewerSubmissionFor($submission);
        Review::create([
            'reviewer_submission_id' => $rs3->id,
            'public_comment' => 'Draft comment',
            'submitted_at' => null,
        ]);

        // 4. Conflit déclaré (ne doit PAS être visible)
        $rs4 = $this->reviewerSubmissionFor($submission);
        Review::create([
            'reviewer_submission_id' => $rs4->id,
            'conflict_of_interest' => true,
            'submitted_at' => null,
        ]);

        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->get("/api/author/submissions/{$submission->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data.reviews')
            ->assertJsonPath('data.reviews.0.public_comment', 'Commentaire public 1')
            ->assertJsonPath('data.reviews.1.public_comment', 'Commentaire public 2');

        // Vérifications de sécurité strictes
        $this->assertArrayNotHasKey('publicReviews', $response->json('data'));
        $this->assertArrayNotHasKey('public_reviews', $response->json('data'));

        $reviews = $response->json('data.reviews');
        foreach ($reviews as $review) {
            $this->assertArrayNotHasKey('private_comment', $review);
            $this->assertArrayNotHasKey('id', $review);
            $this->assertArrayNotHasKey('reviewer_id', $review);
            $this->assertArrayNotHasKey('reviewer_submission_id', $review);
            $this->assertArrayNotHasKey('reviewer', $review);
        }
    }

    public function test_author_gets_empty_reviews_list_if_none_submitted(): void
    {
        $author = $this->author();
        [$conference, $topic] = $this->conferenceAndTopic();
        $submission = $this->submissionFor($author, $conference, $topic);

        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->get("/api/author/submissions/{$submission->id}");

        $response->assertOk()
            ->assertJsonCount(0, 'data.reviews')
            ->assertJsonPath('data.reviews', []);
    }

    private function author(string $email = 'author@example.test'): User
    {
        return User::factory()->create([
            'email' => $email,
            'role' => User::ROLE_AUTHOR,
        ]);
    }

    private function conferenceAndTopic(string $conferenceName = 'SunuConference'): array
    {
        $chair = User::factory()->create([
            'role' => User::ROLE_CHAIR,
        ]);

        $conference = Conference::create([
            'chair_id' => $chair->id,
            'name' => $conferenceName,
            'acronym' => fake()->unique()->bothify('SUNU??##'),
            'description' => 'Conference academique.',
            'call_for_papers' => 'Appel a communications.',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'submission_deadline' => now()->addDay(),
            'review_deadline' => now()->addMonth(),
            'camera_ready_deadline' => now()->addMonths(2),
            'status' => Conference::STATUS_VALIDATED,
            'program_published' => false,
        ]);

        $topic = $conference->topics()->create([
            'name' => fake()->unique()->words(3, true),
            'description' => null,
        ]);

        return [$conference, $topic];
    }

    private function submissionFor(User $author, Conference $conference, Topic $topic, array $overrides = []): Submission
    {
        return Submission::create(array_merge([
            'conference_id' => $conference->id,
            'topic_id' => $topic->id,
            'author_id' => $author->id,
            'title' => 'Article SunuConference',
            'abstract' => 'Resume detaille de l article.',
            'keywords' => ['laravel', 'conference'],
            'initial_file_path' => "submissions/{$conference->id}/initial.pdf",
            'status' => Submission::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ], $overrides));
    }

    private function reviewerSubmissionFor(Submission $submission): ReviewerSubmission
    {
        $reviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);

        return ReviewerSubmission::create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'assigned_at' => now(),
        ]);
    }
}
