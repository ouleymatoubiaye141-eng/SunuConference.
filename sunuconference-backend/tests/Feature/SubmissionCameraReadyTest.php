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

class SubmissionCameraReadyTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_submit_valid_camera_ready_before_deadline(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(cameraReadyDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);

        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->cameraReadyUrl($submission), $this->validPayload());

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Version camera-ready soumise avec succès.')
            ->assertJsonPath('data.status', Submission::STATUS_CAMERA_READY_SUBMITTED);

        $submission->refresh();

        $this->assertSame(Submission::STATUS_CAMERA_READY_SUBMITTED, $submission->status);
        $this->assertNotNull($submission->camera_ready_submitted_at);
        $this->assertSame("submissions/{$conference->id}/{$submission->id}/camera_ready.pdf", $submission->camera_ready_file_path);
        Storage::disk('local')->assertExists($submission->camera_ready_file_path);
    }

    public function test_camera_ready_is_refused_when_status_is_not_accepted(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(cameraReadyDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);

        foreach ([Submission::STATUS_SUBMITTED, Submission::STATUS_REVISION_REQUIRED] as $status) {
            $submission = $this->submissionFor($author, $conference, $topic, [
                'status' => $status,
            ]);

            $this
                ->withHeaders(['Accept' => 'application/json'])
                ->withToken($author->createToken("api-token-{$status}")->plainTextToken)
                ->post($this->cameraReadyUrl($submission), $this->validPayload())
                ->assertStatus(409)
                ->assertJsonPath('message', 'Cette soumission ne peut pas recevoir de version camera-ready.');

            $submission->refresh();

            $this->assertSame($status, $submission->status);
            $this->assertNull($submission->camera_ready_file_path);
        }
    }

    public function test_camera_ready_is_refused_after_deadline(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(cameraReadyDeadline: now()->subMinute());
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->cameraReadyUrl($submission), $this->validPayload())
            ->assertStatus(409)
            ->assertJsonPath('message', 'La date limite de dépôt camera-ready pour cette conférence est dépassée.');

        $submission->refresh();

        $this->assertSame(Submission::STATUS_ACCEPTED, $submission->status);
        $this->assertNull($submission->camera_ready_file_path);
    }

    public function test_camera_ready_file_must_be_pdf(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(cameraReadyDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->cameraReadyUrl($submission), $this->validPayload([
                'file' => UploadedFile::fake()->create('camera-ready.txt', 20, 'text/plain'),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_camera_ready_file_must_not_exceed_ten_megabytes(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(cameraReadyDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->cameraReadyUrl($submission), $this->validPayload([
                'file' => UploadedFile::fake()->create('camera-ready.pdf', 10241, 'application/pdf'),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        $submission->refresh();

        $this->assertSame(Submission::STATUS_ACCEPTED, $submission->status);
        $this->assertNull($submission->camera_ready_file_path);
    }

    public function test_author_cannot_submit_camera_ready_for_another_authors_submission(): void
    {
        Storage::fake('local');

        $author = $this->author('author@example.test');
        $otherAuthor = $this->author('other@example.test');
        [, $conference] = $this->conferenceForChair(cameraReadyDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($otherAuthor, $conference, $topic);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->cameraReadyUrl($submission), $this->validPayload())
            ->assertNotFound();

        $submission->refresh();

        $this->assertSame(Submission::STATUS_ACCEPTED, $submission->status);
        $this->assertNull($submission->camera_ready_file_path);
    }

    public function test_chair_and_reviewer_cannot_submit_camera_ready(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [$chair, $conference] = $this->conferenceForChair(cameraReadyDeadline: now()->addDay());
        $reviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($chair->createToken('api-token')->plainTextToken)
            ->post($this->cameraReadyUrl($submission), $this->validPayload())
            ->assertForbidden();

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($reviewer->createToken('api-token')->plainTextToken)
            ->post($this->cameraReadyUrl($submission), $this->validPayload())
            ->assertForbidden();
    }

    public function test_camera_ready_does_not_update_submission_metadata(): void
    {
        Storage::fake('local');

        $author = $this->author();
        [, $conference] = $this->conferenceForChair(cameraReadyDeadline: now()->addDay());
        $topic = $this->topicForConference($conference);
        $submission = $this->submissionFor($author, $conference, $topic, [
            'title' => 'Titre accepté',
            'abstract' => 'Résumé accepté.',
            'keywords' => ['accepté', 'final'],
        ]);

        $this
            ->withHeaders(['Accept' => 'application/json'])
            ->withToken($author->createToken('api-token')->plainTextToken)
            ->post($this->cameraReadyUrl($submission), array_merge($this->validPayload(), [
                'title' => 'Titre à ignorer',
                'abstract' => 'Résumé à ignorer.',
                'keywords' => ['à', 'ignorer'],
            ]))
            ->assertOk();

        $submission->refresh();

        $this->assertSame('Titre accepté', $submission->title);
        $this->assertSame('Résumé accepté.', $submission->abstract);
        $this->assertSame(['accepté', 'final'], $submission->keywords);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'file' => UploadedFile::fake()->create('camera-ready.pdf', 500, 'application/pdf'),
        ], $overrides);
    }

    private function cameraReadyUrl(Submission $submission): string
    {
        return "/api/author/submissions/{$submission->id}/camera-ready";
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

    private function conferenceForChair(mixed $cameraReadyDeadline): array
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
            'submission_deadline' => now()->subMonths(3),
            'review_deadline' => now()->subMonths(2),
            'revision_deadline' => now()->subMonth(),
            'camera_ready_deadline' => $cameraReadyDeadline,
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
            'revised_file_path' => "submissions/{$conference->id}/revised.pdf",
            'status' => Submission::STATUS_ACCEPTED,
            'submitted_at' => now()->subMonths(3),
            'revised_at' => now()->subMonth(),
        ], $overrides));
    }
}
