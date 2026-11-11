<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\Decision;
use App\Models\Review;
use App\Models\ReviewerSubmission;
use App\Models\Submission;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\SubmissionDecisionRecordedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ChairDecisionTest extends TestCase
{
    use RefreshDatabase;

    private User $chair;
    private string $token;
    private Conference $conference;
    private Topic $topic;
    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->chair = User::factory()->create(['role' => User::ROLE_CHAIR]);
        $this->token = $this->chair->createToken('api-token')->plainTextToken;
        
        $this->conference = Conference::create([
            'chair_id' => $this->chair->id,
            'name' => 'SunuConference 2026',
            'acronym' => 'SUNU2026',
            'description' => 'Description',
            'call_for_papers' => 'CFP',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'submission_deadline' => '2026-08-01',
            'review_deadline' => '2026-09-01',
            'revision_deadline' => '2026-09-10',
            'camera_ready_deadline' => '2026-09-20',
            'status' => Conference::STATUS_VALIDATED,
        ]);

        $this->topic = $this->conference->topics()->create([
            'name' => 'Topic 1',
        ]);
        
        $author = User::factory()->create(['role' => User::ROLE_AUTHOR]);

        $this->submission = Submission::create([
            'conference_id' => $this->conference->id,
            'topic_id' => $this->topic->id,
            'author_id' => $author->id,
            'title' => 'Title',
            'abstract' => 'Abstract',
            'keywords' => ['k1'],
            'initial_file_path' => 'path/to/file.pdf',
            'status' => Submission::STATUS_UNDER_REVIEW,
            'submitted_at' => now(),
        ]);
    }

    /**
     * 1. Chair accepte une soumission avec au moins 1 review soumise.
     */
    public function test_chair_can_accept_submission_with_at_least_one_submitted_review(): void
    {
        $this->createSubmittedReview($this->submission);

        $response = $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_ACCEPT,
                'comments' => 'Excellent travail.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Décision enregistrée avec succès.')
            ->assertJsonPath('data.decision', Decision::DECISION_ACCEPT);

        $this->submission->refresh();
        $this->assertEquals(Submission::STATUS_ACCEPTED, $this->submission->status);
        $this->assertDatabaseHas('decisions', [
            'submission_id' => $this->submission->id,
            'chair_id' => $this->chair->id,
            'decision' => Decision::DECISION_ACCEPT,
        ]);
    }

    /**
     * 2. Chair rejette SANS comments -> 422.
     */
    public function test_chair_cannot_reject_without_comments(): void
    {
        $this->createSubmittedReview($this->submission);

        $response = $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_REJECT,
                'comments' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comments']);
    }

    /**
     * 3. Chair demande une révision avec comments.
     */
    public function test_chair_can_request_revision_with_comments(): void
    {
        $this->createSubmittedReview($this->submission);

        $response = $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_REVISION_REQUIRED,
                'comments' => 'Des corrections sont nécessaires.',
            ]);

        $response->assertStatus(201);
        
        $this->submission->refresh();
        $this->assertEquals(Submission::STATUS_REVISION_REQUIRED, $this->submission->status);
    }

    /**
     * 4. Décision refusée si aucune review soumise -> 409.
     */
    public function test_decision_refused_if_no_review_submitted(): void
    {
        // On crée une assignation mais pas de review soumise
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $this->submission->reviewerSubmissions()->create([
            'reviewer_id' => $reviewer->id,
            'assigned_by' => $this->chair->id,
            'assigned_at' => now(),
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_ACCEPT,
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', "Impossible de prendre une décision sans au moins un rapport d'évaluation soumis.");
    }

    /**
     * 5. Décision refusée si statut Submission n'est pas under_review ou revised_submitted.
     */
    public function test_decision_refused_if_submission_status_is_invalid(): void
    {
        $this->createSubmittedReview($this->submission);
        $this->submission->update(['status' => Submission::STATUS_SUBMITTED]);

        $response = $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_ACCEPT,
            ]);

        $response->assertStatus(409);

        $this->submission->update(['status' => Submission::STATUS_ACCEPTED]);
        $response = $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_REJECT,
                'comments' => 'Trop tard.',
            ]);

        $response->assertStatus(409);
    }

    /**
     * 6. Warnings présents si 1 review sur 3 assignées est soumise.
     */
    public function test_warnings_present_if_reviews_are_missing(): void
    {
        // 1 soumise
        $this->createSubmittedReview($this->submission);
        
        // 2 en attente (sans review ou en draft)
        $reviewer2 = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $this->submission->reviewerSubmissions()->create([
            'reviewer_id' => $reviewer2->id,
            'assigned_by' => $this->chair->id,
            'assigned_at' => now(),
        ]);

        $reviewer3 = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs3 = $this->submission->reviewerSubmissions()->create([
            'reviewer_id' => $reviewer3->id,
            'assigned_by' => $this->chair->id,
            'assigned_at' => now(),
        ]);
        
        $rs3->review()->create([
            'originality_score' => 3,
            'relevance_score' => 3,
            'presentation_score' => 3,
            'overall_score' => 3,
            'recommendation' => 'weak_accept',
            'public_comment' => 'Draft',
            'submitted_at' => null,
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_ACCEPT,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('warnings.0', "2 reviewer(s) sur 3 n'ont pas encore soumis leur rapport.");
    }

    /**
     * 7. PAS de warnings si toutes les reviews sont soumises ou en conflit.
     */
    public function test_no_warnings_if_all_reviews_submitted_or_conflict(): void
    {
        // 1 soumise
        $this->createSubmittedReview($this->submission);
        
        // 1 en conflit
        $reviewer2 = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs2 = $this->submission->reviewerSubmissions()->create([
            'reviewer_id' => $reviewer2->id,
            'assigned_by' => $this->chair->id,
            'assigned_at' => now(),
        ]);
        
        $rs2->review()->create([
            'conflict_of_interest' => true,
            'submitted_at' => null,
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_ACCEPT,
            ]);

        $response->assertStatus(201);
        $this->assertArrayNotHasKey('warnings', $response->json());
    }

    /**
     * 8. Chair d'une autre conférence -> 403.
     */
    public function test_other_chair_cannot_record_decision(): void
    {
        $otherChair = User::factory()->create(['role' => User::ROLE_CHAIR]);
        $otherToken = $otherChair->createToken('api-token')->plainTextToken;
        $this->createSubmittedReview($this->submission);

        $response = $this->withToken($otherToken)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_ACCEPT,
            ]);

        $response->assertStatus(403);
    }

    /**
     * 9. Submission mismatch conference URL -> 404.
     */
    public function test_submission_mismatch_conference_returns_404(): void
    {
        $otherConf = Conference::create([
            'chair_id' => $this->chair->id,
            'name' => 'Other',
            'acronym' => 'OTHER',
            'description' => 'Desc',
            'call_for_papers' => 'CFP',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'submission_deadline' => '2026-08-01',
            'review_deadline' => '2026-09-01',
            'revision_deadline' => '2026-09-10',
            'camera_ready_deadline' => '2026-09-20',
            'status' => Conference::STATUS_VALIDATED,
        ]);
        
        $this->createSubmittedReview($this->submission);

        $response = $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$otherConf->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_ACCEPT,
            ]);

        $response->assertStatus(404);
    }

    /**
     * 10. Historique : deux décisions successives sur la même soumission.
     */
    public function test_decisions_history_is_preserved(): void
    {
        $this->createSubmittedReview($this->submission);

        // Première décision : révision
        $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_REVISION_REQUIRED,
                'comments' => 'V1',
            ]);

        // Simuler le passage en revised_submitted pour permettre une 2e décision
        $this->submission->update(['status' => Submission::STATUS_REVISED_SUBMITTED]);

        // Deuxième décision : acceptation
        $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_ACCEPT,
                'comments' => 'V2',
            ]);

        $this->assertEquals(2, $this->submission->decisions()->count());
        
        $latest = $this->submission->latestDecision;
        $this->assertEquals(Decision::DECISION_ACCEPT, $latest->decision);
        $this->assertEquals('V2', $latest->comments);
    }

    public function test_author_receives_mail_and_database_notification_when_submission_is_accepted(): void
    {
        Notification::fake();
        $this->createSubmittedReview($this->submission);
        $author = $this->submission->author;

        $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_ACCEPT,
            ])
            ->assertCreated();

        Notification::assertSentTo(
            $author,
            SubmissionDecisionRecordedNotification::class,
            fn (SubmissionDecisionRecordedNotification $notification, array $channels): bool => $channels === ['mail', 'database'],
        );
    }

    public function test_author_receives_rejection_notification_with_comments_in_database_payload(): void
    {
        Notification::fake();
        $this->createSubmittedReview($this->submission);
        $author = $this->submission->author;
        $comments = 'La contribution ne correspond pas au périmètre scientifique.';

        $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_REJECT,
                'comments' => $comments,
            ])
            ->assertCreated();

        Notification::assertSentTo(
            $author,
            SubmissionDecisionRecordedNotification::class,
            fn (SubmissionDecisionRecordedNotification $notification): bool => $notification->toArray($author)['comments'] === $comments,
        );
    }

    public function test_author_receives_notification_when_revision_is_required(): void
    {
        Notification::fake();
        $this->createSubmittedReview($this->submission);
        $author = $this->submission->author;

        $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_REVISION_REQUIRED,
                'comments' => 'Merci de répondre aux remarques des reviewers.',
            ])
            ->assertCreated();

        Notification::assertSentTo($author, SubmissionDecisionRecordedNotification::class);
    }

    public function test_accepted_decision_notification_has_expected_database_payload(): void
    {
        Notification::fake();
        $this->createSubmittedReview($this->submission);
        $author = $this->submission->author;

        $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_ACCEPT,
            ])
            ->assertCreated();

        Notification::assertSentTo(
            $author,
            SubmissionDecisionRecordedNotification::class,
            function (SubmissionDecisionRecordedNotification $notification) use ($author): bool {
                return $notification->toArray($author) === [
                    'event' => 'submission.decision_recorded',
                    'entity_type' => 'submission',
                    'entity_id' => $this->submission->id,
                    'message' => "Votre soumission « {$this->submission->title} » a été acceptée.",
                    'decision' => Decision::DECISION_ACCEPT,
                    'comments' => null,
                ];
            },
        );
    }

    public function test_other_author_is_not_notified_when_decision_is_recorded(): void
    {
        Notification::fake();
        $this->createSubmittedReview($this->submission);
        $author = $this->submission->author;
        $otherAuthor = User::factory()->create(['role' => User::ROLE_AUTHOR]);

        $this->withToken($this->token)
            ->postJson("/api/chair/conferences/{$this->conference->id}/submissions/{$this->submission->id}/decision", [
                'decision' => Decision::DECISION_ACCEPT,
            ])
            ->assertCreated();

        Notification::assertSentTo($author, SubmissionDecisionRecordedNotification::class);
        Notification::assertNotSentTo($otherAuthor, SubmissionDecisionRecordedNotification::class);
    }

    private function createSubmittedReview(Submission $submission): Review
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $rs = $submission->reviewerSubmissions()->create([
            'reviewer_id' => $reviewer->id,
            'assigned_by' => $this->chair->id,
            'assigned_at' => now(),
        ]);

        return $rs->review()->create([
            'originality_score' => 4,
            'relevance_score' => 4,
            'presentation_score' => 4,
            'overall_score' => 4,
            'recommendation' => 'accept',
            'public_comment' => 'Good',
            'submitted_at' => now(),
            'conflict_of_interest' => false,
        ]);
    }
}
