<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\Submission;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_submit_article_to_validated_conference_topic_before_deadline(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED, submissionDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);

        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->submissionUrl($conference, $topic), [
                'title' => 'Article SunuConférence',
                'abstract' => 'Résumé détaillé de l article.',
                'keywords' => ['laravel', 'conference'],
                'file' => UploadedFile::fake()->create('article.pdf', 500, 'application/pdf'),
                'co_authors' => [
                    [
                        'name' => 'Awa Coauthor',
                        'email' => 'awa.coauthor@example.test',
                        'affiliation' => 'Université de Dakar',
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Article soumis avec succès.');

        $submission = Submission::firstOrFail();

        Storage::disk('local')->assertExists($submission->initial_file_path);
        $this->assertSame("submissions/{$conference->id}/{$submission->id}/initial.pdf", $submission->initial_file_path);

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'conference_id' => $conference->id,
            'topic_id' => $topic->id,
            'author_id' => $author->id,
            'title' => 'Article SunuConférence',
            'status' => Submission::STATUS_SUBMITTED,
        ]);

        $this->assertDatabaseHas('submission_authors', [
            'submission_id' => $submission->id,
            'name' => 'Awa Coauthor',
            'email' => 'awa.coauthor@example.test',
            'affiliation' => 'Université de Dakar',
            'position' => 0,
        ]);
    }

    public function test_author_cannot_submit_to_non_validated_conference(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(status: Conference::STATUS_PENDING, submissionDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->submissionUrl($conference, $topic), $this->validPayload())
            ->assertUnprocessable();

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_author_cannot_submit_after_submission_deadline(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED, submissionDeadline: now()->subDay());
        $topic = $this->topicForConference($conference);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->submissionUrl($conference, $topic), $this->validPayload())
            ->assertStatus(409)
            ->assertJsonPath('message', 'La date limite de soumission pour cette conférence est dépassée.');

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_author_can_submit_shortly_before_submission_deadline(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED, submissionDeadline: now()->addMinute());
        $topic = $this->topicForConference($conference);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->submissionUrl($conference, $topic), $this->validPayload())
            ->assertCreated();

        $this->assertDatabaseCount('submissions', 1);
    }

    public function test_submission_file_must_be_pdf(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED, submissionDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);
        $payload = $this->validPayload([
            'file' => UploadedFile::fake()->create('article.txt', 10, 'text/plain'),
        ]);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->submissionUrl($conference, $topic), $payload)
            ->assertUnprocessable();

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_submission_file_must_not_exceed_ten_megabytes(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED, submissionDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);
        $payload = $this->validPayload([
            'file' => UploadedFile::fake()->create('trop_gros.pdf', 10241, 'application/pdf'),
        ]);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->submissionUrl($conference, $topic), $payload)
            ->assertUnprocessable();

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_chair_or_reviewer_cannot_submit_article(): void
    {
        Storage::fake('local');

        [$chair, $conference] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED, submissionDeadline: now()->addDay());
        $reviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);
        $topic = $this->topicForConference($conference);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($chair->createToken('api-token')->plainTextToken)
            ->post($this->submissionUrl($conference, $topic), $this->validPayload())
            ->assertForbidden();

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($reviewer->createToken('api-token')->plainTextToken)
            ->post($this->submissionUrl($conference, $topic), $this->validPayload())
            ->assertForbidden();
    }

    public function test_author_cannot_submit_to_topic_via_mismatched_conference_url(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED, submissionDeadline: now()->addDay());
        [, $otherConference] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED, submissionDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->submissionUrl($otherConference, $topic), $this->validPayload())
            ->assertNotFound();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Article SunuConférence',
            'abstract' => 'Résumé détaillé de l article.',
            'keywords' => ['laravel', 'conference'],
            'file' => UploadedFile::fake()->create('article.pdf', 500, 'application/pdf'),
        ], $overrides);
    }

    private function submissionUrl(Conference $conference, Topic $topic): string
    {
        return "/api/author/conferences/{$conference->id}/topics/{$topic->id}/submissions";
    }

    private function author(): User
    {
        return User::factory()->create([
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

    private function conferenceForChair(
        string $email = 'chair@example.test',
        string $status = Conference::STATUS_VALIDATED,
        mixed $submissionDeadline = null,
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
            'submission_deadline' => $submissionDeadline ?? now()->addDay(),
            'review_deadline' => now()->addMonth(),
            'revision_deadline' => now()->addMonths(2),
            'camera_ready_deadline' => now()->addMonths(3),
            'status' => $status,
            'program_published' => false,
        ]);

        return [$chair, $conference];
    }
}
