<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TopicManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_chair_can_create_topic_in_own_validated_conference(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);

        $response = $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/topics", [
                'name' => 'Intelligence Artificielle',
                'description' => 'Apprentissage automatique.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', "Topic cr\u{00E9}\u{00E9} avec succ\u{00E8}s.")
            ->assertJsonPath('data.name', 'Intelligence Artificielle');

        $this->assertDatabaseHas('topics', [
            'conference_id' => $conference->id,
            'name' => 'Intelligence Artificielle',
            'description' => 'Apprentissage automatique.',
        ]);
    }

    public function test_chair_cannot_create_topic_in_another_chairs_conference(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED);
        [, , $otherToken] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED);

        $this
            ->withToken($otherToken)
            ->postJson("/api/chair/conferences/{$conference->id}/topics", [
                'name' => 'Securite',
                'description' => 'Topic interdit.',
            ])
            ->assertForbidden();
    }

    public function test_chair_cannot_create_topic_when_own_conference_is_pending(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_PENDING);

        $this
            ->withToken($token)
            ->postJson("/api/chair/conferences/{$conference->id}/topics", [
                'name' => 'Systemes',
                'description' => 'Systemes distribues.',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', "Votre conf\u{00E9}rence est en attente de validation par l'administrateur.");

        $this->assertDatabaseMissing('topics', [
            'conference_id' => $conference->id,
            'name' => 'Systemes',
        ]);
    }

    public function test_chair_can_list_topics_for_own_conference(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);

        $conference->topics()->create([
            'name' => 'Reseaux',
            'description' => 'Reseaux et systemes distribues.',
        ]);

        $this
            ->withToken($token)
            ->getJson("/api/chair/conferences/{$conference->id}/topics")
            ->assertOk()
            ->assertJsonPath('message', "Topics r\u{00E9}cup\u{00E9}r\u{00E9}s avec succ\u{00E8}s.")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Reseaux');
    }

    public function test_chair_cannot_list_topics_from_another_chairs_conference(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED);
        [, , $otherToken] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED);

        $this
            ->withToken($otherToken)
            ->getJson("/api/chair/conferences/{$conference->id}/topics")
            ->assertForbidden();
    }

    public function test_chair_can_update_topic_in_own_conference(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);

        $topic = $conference->topics()->create([
            'name' => 'Ancien topic',
            'description' => 'Ancienne description.',
        ]);

        $this
            ->withToken($token)
            ->putJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}", [
                'name' => 'Reseaux',
                'description' => 'Reseaux et systemes distribues.',
            ])
            ->assertOk()
            ->assertJsonPath('message', "Topic modifi\u{00E9} avec succ\u{00E8}s.")
            ->assertJsonPath('data.name', 'Reseaux');

        $this->assertDatabaseHas('topics', [
            'id' => $topic->id,
            'name' => 'Reseaux',
            'description' => 'Reseaux et systemes distribues.',
        ]);
    }

    public function test_chair_cannot_update_topic_via_mismatched_conference_url(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED);
        [, $otherConference, $otherToken] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED);

        $topic = $conference->topics()->create([
            'name' => 'Topic proprietaire',
            'description' => null,
        ]);

        $this
            ->withToken($otherToken)
            ->putJson("/api/chair/conferences/{$otherConference->id}/topics/{$topic->id}", [
                'name' => 'Tentative interdite',
                'description' => 'Ne doit pas passer.',
            ])
            ->assertNotFound();
    }

    public function test_chair_cannot_update_topic_from_another_chairs_conference(): void
    {
        [, $conference] = $this->conferenceForChair(email: 'owner@example.test', status: Conference::STATUS_VALIDATED);
        [, , $otherToken] = $this->conferenceForChair(email: 'other@example.test', status: Conference::STATUS_VALIDATED);

        $topic = $conference->topics()->create([
            'name' => 'Topic proprietaire',
            'description' => null,
        ]);

        $this
            ->withToken($otherToken)
            ->putJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}", [
                'name' => 'Tentative interdite',
                'description' => 'Ne doit pas passer.',
            ])
            ->assertForbidden();
    }

    public function test_chair_can_delete_topic_without_assigned_reviewers(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);

        $topic = $conference->topics()->create([
            'name' => 'A supprimer',
            'description' => null,
        ]);

        $this
            ->withToken($token)
            ->deleteJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}")
            ->assertOk()
            ->assertJsonPath('message', "Topic supprim\u{00E9} avec succ\u{00E8}s.");

        $this->assertDatabaseMissing('topics', [
            'id' => $topic->id,
        ]);
    }

    public function test_chair_cannot_delete_topic_with_assigned_reviewers(): void
    {
        [, $conference, $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);

        $topic = $conference->topics()->create([
            'name' => 'Topic avec relecteur',
            'description' => null,
        ]);

        $reviewer = User::factory()->create([
            'role' => User::ROLE_REVIEWER,
        ]);

        DB::table('reviewer_topic')->insert([
            'topic_id' => $topic->id,
            'reviewer_id' => $reviewer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->withToken($token)
            ->deleteJson("/api/chair/conferences/{$conference->id}/topics/{$topic->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', "Impossible de supprimer ce topic : 1 relecteur(s) y sont d\u{00E9}j\u{00E0} assign\u{00E9}s. Retirez d'abord les assignations avant de supprimer ce topic.");

        $this->assertDatabaseHas('topics', [
            'id' => $topic->id,
        ]);
    }

    public function test_public_route_returns_topics_for_validated_conference(): void
    {
        [, $conference] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);

        $conference->topics()->create([
            'name' => 'Intelligence Artificielle',
            'description' => 'Apprentissage automatique.',
        ]);

        $this
            ->getJson("/api/conferences/{$conference->id}/topics")
            ->assertOk()
            ->assertJsonPath('message', "Topics r\u{00E9}cup\u{00E9}r\u{00E9}s avec succ\u{00E8}s.")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Intelligence Artificielle');
    }

    public function test_public_route_returns_not_found_for_non_validated_or_missing_conference(): void
    {
        [, $conference] = $this->conferenceForChair(status: Conference::STATUS_PENDING);

        $this
            ->getJson("/api/conferences/{$conference->id}/topics")
            ->assertNotFound()
            ->assertJsonPath('message', "Conf\u{00E9}rence introuvable.");

        $this
            ->getJson('/api/conferences/999999/topics')
            ->assertNotFound();
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
            'description' => 'Conference academique.',
            'call_for_papers' => 'Appel a communications.',
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
}
