<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\User;
use App\Notifications\ConferenceRejectedNotification;
use App\Notifications\ConferenceValidatedNotification;
use App\Services\ConferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthAndConferenceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_chair_conference_flow_until_admin_validation(): void
    {
        Notification::fake();

        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Awa Chair',
            'email' => 'awa@example.test',
            'affiliation' => 'Université Cheikh Anta Diop',
            'role' => User::ROLE_CHAIR,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonPath('data.user.role', User::ROLE_CHAIR)
            ->assertJsonStructure(['data' => ['token']]);

        $chairToken = $registerResponse->json('data.token');

        $conferenceResponse = $this
            ->withToken($chairToken)
            ->postJson('/api/chair/conferences', $this->conferenceData());

        $conferenceResponse
            ->assertCreated()
            ->assertJsonPath('data.status', Conference::STATUS_PENDING);

        $conferenceId = $conferenceResponse->json('data.id');

        $this
            ->withToken($chairToken)
            ->getJson('/api/chair/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', "Votre conférence est en attente de validation par l'administrateur.");

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Sanctum::actingAs($admin);

        $this
            ->postJson("/api/admin/conferences/{$conferenceId}/validate")
            ->assertOk()
            ->assertJsonPath('data.status', Conference::STATUS_VALIDATED);

        $this->app['auth']->forgetGuards();

        $this
            ->withToken($chairToken)
            ->getJson('/api/chair/dashboard')
            ->assertOk()
            ->assertJsonPath('message', 'Acces PC Chair autorise.');
    }

    public function test_reviewer_cannot_register_themselves(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Reviewer',
            'email' => 'reviewer@example.test',
            'role' => User::ROLE_REVIEWER,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable();
    }

    public function test_chair_receives_mail_and_database_notification_when_conference_is_validated(): void
    {
        Notification::fake();
        $chair = User::factory()->create(['role' => User::ROLE_CHAIR]);
        $conference = $this->conferenceForChair($chair);

        app(ConferenceService::class)->validate($conference);

        Notification::assertSentTo(
            $chair,
            ConferenceValidatedNotification::class,
            fn (ConferenceValidatedNotification $notification, array $channels): bool => $channels === ['mail', 'database'],
        );
    }

    public function test_chair_receives_mail_and_database_notification_when_conference_is_rejected_with_reason(): void
    {
        Notification::fake();
        $chair = User::factory()->create(['role' => User::ROLE_CHAIR]);
        $conference = $this->conferenceForChair($chair);
        $reason = 'Les dates soumises sont incohérentes.';

        app(ConferenceService::class)->reject($conference, $reason);

        Notification::assertSentTo(
            $chair,
            ConferenceRejectedNotification::class,
            function (ConferenceRejectedNotification $notification, array $channels) use ($chair, $conference, $reason): bool {
                return $channels === ['mail', 'database']
                    && $notification->toArray($chair) === [
                        'event' => 'conference.rejected',
                        'entity_type' => 'conference',
                        'entity_id' => $conference->id,
                        'message' => "Votre conférence « {$conference->name} » a été rejetée.",
                        'reason' => $reason,
                    ];
            },
        );
    }

    public function test_validated_notification_has_expected_database_payload(): void
    {
        Notification::fake();
        $chair = User::factory()->create(['role' => User::ROLE_CHAIR]);
        $conference = $this->conferenceForChair($chair);

        app(ConferenceService::class)->validate($conference);

        Notification::assertSentTo(
            $chair,
            ConferenceValidatedNotification::class,
            function (ConferenceValidatedNotification $notification) use ($chair, $conference): bool {
                return $notification->toArray($chair) === [
                    'event' => 'conference.validated',
                    'entity_type' => 'conference',
                    'entity_id' => $conference->id,
                    'message' => "Votre conférence « {$conference->name} » a été validée.",
                ];
            },
        );
    }

    public function test_other_chair_is_not_notified_when_conference_is_validated(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['role' => User::ROLE_CHAIR]);
        $otherChair = User::factory()->create(['role' => User::ROLE_CHAIR]);
        $conference = $this->conferenceForChair($owner);

        app(ConferenceService::class)->validate($conference);

        Notification::assertSentTo($owner, ConferenceValidatedNotification::class);
        Notification::assertNotSentTo($otherChair, ConferenceValidatedNotification::class);
    }

    private function conferenceForChair(User $chair): Conference
    {
        return Conference::create([
            'chair_id' => $chair->id,
            ...$this->conferenceData(),
            'status' => Conference::STATUS_PENDING,
            'program_published' => false,
        ]);
    }

    private function conferenceData(): array
    {
        return [
            'name' => 'SunuConférence 2026',
            'acronym' => fake()->unique()->bothify('SUNU??##'),
            'description' => 'Conférence académique.',
            'call_for_papers' => 'Appel à communications.',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'submission_deadline' => '2026-08-01',
            'review_deadline' => '2026-09-01',
            'revision_deadline' => '2026-09-10',
            'camera_ready_deadline' => '2026-09-20',
        ];
    }
}
