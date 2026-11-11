<?php

namespace Tests\Feature;

use App\Models\AuthorResponse;
use App\Models\Conference;
use App\Models\Review;
use App\Models\ReviewerSubmission;
use App\Models\Submission;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionRebuttalTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_submit_pre_decision_rebuttal_when_all_non_conflicting_reviews_are_submitted(): void
    {
        $author = $this->author();
        [, $conference] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);

        $this->submittedReviewFor($submission);
        $this->submittedReviewFor($submission);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->postJson($this->rebuttalUrl($submission), [
                'content' => 'Merci pour les retours, voici notre clarification.',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Rebuttal pré-décision soumis avec succès.')
            ->assertJsonPath('data.type', AuthorResponse::TYPE_PRE_DECISION)
            ->assertJsonPath('data.content', 'Merci pour les retours, voici notre clarification.');

        $submission->refresh();

        $this->assertSame(Submission::STATUS_UNDER_REVIEW, $submission->status);
        $this->assertDatabaseHas('author_responses', [
            'submission_id' => $submission->id,
            'author_id' => $author->id,
            'type' => AuthorResponse::TYPE_PRE_DECISION,
            'content' => 'Merci pour les retours, voici notre clarification.',
        ]);
    }

    public function test_author_can_submit_rebuttal_when_one_reviewer_declared_conflict_and_others_submitted(): void
    {
        $author = $this->author();
        [, $conference] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);

        $this->submittedReviewFor($submission);
        $this->conflictReviewFor($submission);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->postJson($this->rebuttalUrl($submission), [
                'content' => 'Un évaluateur est en conflit, les autres rapports sont complets.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', AuthorResponse::TYPE_PRE_DECISION);

        $this->assertDatabaseHas('author_responses', [
            'submission_id' => $submission->id,
            'type' => AuthorResponse::TYPE_PRE_DECISION,
            'content' => 'Un évaluateur est en conflit, les autres rapports sont complets.',
        ]);
    }

    public function test_rebuttal_is_refused_while_non_conflicting_review_is_draft_or_not_started(): void
    {
        $author = $this->author();
        [, $conference] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);

        $this->submittedReviewFor($submission);
        $this->draftReviewFor($submission);
        $this->notStartedAssignmentFor($submission);
        $this->conflictReviewFor($submission);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->postJson($this->rebuttalUrl($submission), [
                'content' => 'Réponse avant complétude.',
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', "2 rapports d'évaluation non conflictuels sont encore attendus.");

        $this->assertDatabaseCount('author_responses', 0);
    }

    public function test_rebuttal_is_refused_when_submission_is_not_under_review(): void
    {
        $author = $this->author();
        [, $conference] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);

        foreach ([Submission::STATUS_SUBMITTED, Submission::STATUS_ACCEPTED] as $status) {
            $submission = $this->submissionFor($author, $conference, $topic, [
                'status' => $status,
            ]);

            $this
                ->withHeaders(['Accept' => 'application/json'])
                ->withToken($author->createToken("api-token-{$status}")->plainTextToken)
                ->postJson($this->rebuttalUrl($submission), [
                    'content' => 'Réponse au mauvais statut.',
                ])
                ->assertStatus(409)
                ->assertJsonPath('message', 'Cette soumission ne peut pas recevoir de rebuttal pré-décision.');
        }

        $this->assertDatabaseCount('author_responses', 0);
    }

    public function test_author_can_submit_multiple_pre_decision_rebuttals(): void
    {
        $author = $this->author();
        [, $conference] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);
        $this->submittedReviewFor($submission);

        $token = $author->createToken('api-token')->plainTextToken;

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($token)
            ->postJson($this->rebuttalUrl($submission), [
                'content' => 'Premier rebuttal.',
            ])
            ->assertCreated();

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($token)
            ->postJson($this->rebuttalUrl($submission), [
                'content' => 'Deuxième rebuttal.',
            ])
            ->assertCreated();

        $this->assertDatabaseCount('author_responses', 2);
        $this->assertDatabaseHas('author_responses', [
            'submission_id' => $submission->id,
            'author_id' => $author->id,
            'type' => AuthorResponse::TYPE_PRE_DECISION,
            'content' => 'Premier rebuttal.',
        ]);
        $this->assertDatabaseHas('author_responses', [
            'submission_id' => $submission->id,
            'author_id' => $author->id,
            'type' => AuthorResponse::TYPE_PRE_DECISION,
            'content' => 'Deuxième rebuttal.',
        ]);
    }

    public function test_author_cannot_submit_rebuttal_for_another_authors_submission(): void
    {
        $author = $this->author('author@example.test');
        $otherAuthor = $this->author('other@example.test');
        [, $conference] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($otherAuthor, $conference, $topic);
        $this->submittedReviewFor($submission);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->postJson($this->rebuttalUrl($submission), [
                'content' => 'Tentative non autorisée.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('author_responses', 0);
    }

    public function test_chair_and_reviewer_cannot_submit_rebuttal(): void
    {
        $author = $this->author();
        [$chair, $conference] = $this->conferenceForChair();
        $reviewer = $this->reviewer();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);
        $this->submittedReviewFor($submission);

        $payload = ['content' => 'Réponse auteur.'];

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($chair->createToken('api-token')->plainTextToken)
            ->postJson($this->rebuttalUrl($submission), $payload)
            ->assertForbidden();

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($reviewer->createToken('api-token')->plainTextToken)
            ->postJson($this->rebuttalUrl($submission), $payload)
            ->assertForbidden();
    }

    public function test_rebuttal_content_is_required_not_empty_and_not_too_long(): void
    {
        $author = $this->author();
        [, $conference] = $this->conferenceForChair();
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);
        $this->submittedReviewFor($submission);
        $token = $author->createToken('api-token')->plainTextToken;

        foreach ([[], ['content' => ''], ['content' => str_repeat('a', 5001)]] as $payload) {
            $this
                ->withHeaders(['Accept' => 'application/json'])
                ->withToken($token)
                ->postJson($this->rebuttalUrl($submission), $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['content']);
        }

        $this->assertDatabaseCount('author_responses', 0);
    }

    private function submittedReviewFor(Submission $submission): Review
    {
        $assignment = $this->assignmentFor($submission);

        return Review::create([
            'reviewer_submission_id' => $assignment->id,
            'originality_score' => 4,
            'relevance_score' => 4,
            'presentation_score' => 4,
            'overall_score' => 4,
            'public_comment' => 'Article solide.',
            'recommendation' => Review::RECOMMENDATION_ACCEPT,
            'submitted_at' => now(),
        ]);
    }

    private function draftReviewFor(Submission $submission): Review
    {
        $assignment = $this->assignmentFor($submission);

        return Review::create([
            'reviewer_submission_id' => $assignment->id,
            'public_comment' => 'Brouillon.',
            'submitted_at' => null,
        ]);
    }

    private function conflictReviewFor(Submission $submission): Review
    {
        $assignment = $this->assignmentFor($submission);

        return Review::create([
            'reviewer_submission_id' => $assignment->id,
            'conflict_of_interest' => true,
            'conflict_reason' => 'Même laboratoire.',
        ]);
    }

    private function notStartedAssignmentFor(Submission $submission): ReviewerSubmission
    {
        return $this->assignmentFor($submission);
    }

    private function assignmentFor(Submission $submission): ReviewerSubmission
    {
        return ReviewerSubmission::create([
            'submission_id' => $submission->id,
            'reviewer_id' => $this->reviewer()->id,
            'assigned_at' => now(),
        ]);
    }

    private function rebuttalUrl(Submission $submission): string
    {
        return "/api/author/submissions/{$submission->id}/rebuttal";
    }

    private function author(string $email = 'author@example.test'): User
    {
        return User::factory()->create([
            'email' => $email,
            'role' => User::ROLE_AUTHOR,
        ]);
    }

    private function reviewer(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);
    }

    private function topicForConference(Conference $conference): Topic
    {
        return $conference->topics()->create([
            'name' => fake()->unique()->words(3, true),
            'description' => null,
        ]);
    }

    private function conferenceForChair(): array
    {
        $chair = User::factory()->create([
            'role' => User::ROLE_CHAIR,
        ]);

        $conference = Conference::create([
            'chair_id' => $chair->id,
            'name' => 'SunuConférence 2026',
            'acronym' => fake()->unique()->bothify('SUNU??##'),
            'description' => 'Conférence académique.',
            'call_for_papers' => 'Appel à communications.',
            'start_date' => now()->addMonths(4),
            'end_date' => now()->addMonths(4)->addDays(2),
            'submission_deadline' => now()->subMonths(2),
            'review_deadline' => now()->subMonth(),
            'revision_deadline' => now()->addMonth(),
            'camera_ready_deadline' => now()->addMonths(2),
            'status' => Conference::STATUS_VALIDATED,
            'program_published' => false,
        ]);

        return [$chair, $conference];
    }

    private function submissionFor(
        User $author,
        Conference $conference,
        Topic $topic,
        array $overrides = [],
    ): Submission {
        return Submission::create(array_merge([
            'conference_id' => $conference->id,
            'topic_id' => $topic->id,
            'author_id' => $author->id,
            'title' => 'Article SunuConférence',
            'abstract' => 'Résumé détaillé de l’article.',
            'keywords' => ['laravel', 'conférence'],
            'initial_file_path' => "submissions/{$conference->id}/initial.pdf",
            'status' => Submission::STATUS_UNDER_REVIEW,
            'submitted_at' => now()->subMonths(2),
        ], $overrides));
    }
}
