<?php

namespace Tests\Feature;

use App\Models\AuthorResponse;
use App\Models\Conference;
use App\Models\Submission;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_submit_valid_revision_before_deadline(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(revisionDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);

        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->revisionUrl($submission), $this->validPayload([
                'response_to_reviewers' => 'Nous avons répondu à tous les commentaires.',
            ]));

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Révision soumise avec succès.')
            ->assertJsonPath('data.status', Submission::STATUS_REVISED_SUBMITTED);

        $submission->refresh();

        $this->assertSame(Submission::STATUS_REVISED_SUBMITTED, $submission->status);
        $this->assertNotNull($submission->revised_at);
        $this->assertSame("submissions/{$conference->id}/{$submission->id}/revised.pdf", $submission->revised_file_path);
        Storage::disk('local')->assertExists($submission->revised_file_path);

        $this->assertDatabaseHas('author_responses', [
            'submission_id' => $submission->id,
            'author_id' => $author->id,
            'type' => AuthorResponse::TYPE_REVISION_RESPONSE,
            'content' => 'Nous avons répondu à tous les commentaires.',
        ]);
    }

    public function test_optional_metadata_is_updated_when_provided(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(revisionDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic, [
            'title' => 'Ancien titre',
            'abstract' => 'Ancien résumé',
            'keywords' => ['ancien'],
        ]);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->revisionUrl($submission), $this->validPayload([
                'title' => 'Titre révisé',
                'abstract' => 'Résumé révisé et détaillé.',
                'keywords' => ['révision', 'laravel'],
            ]))
            ->assertOk()
            ->assertJsonPath('data.title', 'Titre révisé')
            ->assertJsonPath('data.abstract', 'Résumé révisé et détaillé.')
            ->assertJsonPath('data.keywords.0', 'révision')
            ->assertJsonPath('data.keywords.1', 'laravel');

        $submission->refresh();

        $this->assertSame('Titre révisé', $submission->title);
        $this->assertSame('Résumé révisé et détaillé.', $submission->abstract);
        $this->assertSame(['révision', 'laravel'], $submission->keywords);
    }

    public function test_revision_is_refused_when_status_is_not_revision_required(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(revisionDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);

        foreach ([Submission::STATUS_SUBMITTED, Submission::STATUS_ACCEPTED] as $status) {
            $submission = $this->submissionFor($author, $conference, $topic, [
                'status' => $status,
            ]);

            $this
                ->withHeaders(['Accept' => 'application/json'])
                ->withToken($author->createToken("api-token-{$status}")->plainTextToken)
                ->post($this->revisionUrl($submission), $this->validPayload())
                ->assertStatus(409)
                ->assertJsonPath('message', 'Cette soumission ne peut pas recevoir de révision.');

            $submission->refresh();

            $this->assertSame($status, $submission->status);
            $this->assertNull($submission->revised_file_path);
        }
    }

    public function test_revision_is_refused_after_revision_deadline(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(revisionDeadline: now()->subMinute());
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->revisionUrl($submission), $this->validPayload())
            ->assertStatus(409)
            ->assertJsonPath('message', 'La date limite de révision pour cette conférence est dépassée.');

        $submission->refresh();

        $this->assertSame(Submission::STATUS_REVISION_REQUIRED, $submission->status);
        $this->assertNull($submission->revised_file_path);
        $this->assertDatabaseCount('author_responses', 0);
    }

    public function test_revision_file_must_be_pdf(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(revisionDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->revisionUrl($submission), $this->validPayload([
                'file' => UploadedFile::fake()->create('revision.txt', 20, 'text/plain'),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_response_to_reviewers_is_required(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(revisionDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);
        $payload = $this->validPayload();
        unset($payload['response_to_reviewers']);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->revisionUrl($submission), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['response_to_reviewers']);
    }

    public function test_author_cannot_submit_revision_for_another_authors_submission(): void
    {
        Storage::fake('local');

        $author = $this->author('author@example.test');
        $otherAuthor = $this->author('other@example.test');
        [, $conference] = $this->conferenceForChair(revisionDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($otherAuthor, $conference, $topic);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->revisionUrl($submission), $this->validPayload())
            ->assertNotFound();

        $submission->refresh();

        $this->assertSame(Submission::STATUS_REVISION_REQUIRED, $submission->status);
        $this->assertNull($submission->revised_file_path);
    }

    public function test_chair_and_reviewer_cannot_submit_revision(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [$chair, $conference] = $this->conferenceForChair(revisionDeadline: now()->addDay());
        $reviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($chair->createToken('api-token')->plainTextToken)
            ->post($this->revisionUrl($submission), $this->validPayload())
            ->assertForbidden();

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($reviewer->createToken('api-token')->plainTextToken)
            ->post($this->revisionUrl($submission), $this->validPayload())
            ->assertForbidden();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'file' => UploadedFile::fake()->create('revision.pdf', 500, 'application/pdf'),
            'response_to_reviewers' => 'Réponse détaillée aux évaluateurs.',
        ], $overrides);
    }

    private function revisionUrl(Submission $submission): string
    {
        return "/api/author/submissions/{$submission->id}/revision";
    }

    private function author(string $email = 'author@example.test'): User
    {
        return User::factory()->create([
            'email' => $email,
            'role' => User::ROLE_AUTHOR,
        ]);
    }

    private function topicForConference(Conference $conference): Topic
    {
        return $conference->topics()->create([
            'name' => fake()->unique()->words(3, true),
            'description' => null,
        ]);
    }

    private function conferenceForChair(mixed $revisionDeadline): array
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
            'revision_deadline' => $revisionDeadline,
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
            'status' => Submission::STATUS_REVISION_REQUIRED,
            'submitted_at' => now()->subMonths(2),
        ], $overrides));
    }
}
