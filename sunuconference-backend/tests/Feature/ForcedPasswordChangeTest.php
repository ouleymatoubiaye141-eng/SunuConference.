<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_must_change_password_before_accessing_protected_routes(): void
    {
        $reviewer = $this->reviewerWhoMustChangePassword();

        $this
            ->withToken($reviewer->createToken('api-token')->plainTextToken)
            ->getJson('/api/reviewer/topics')
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous devez changer votre mot de passe avant de continuer.');
    }

    public function test_reviewer_who_must_change_password_can_access_allowed_auth_routes(): void
    {
        $reviewer = $this->reviewerWhoMustChangePassword();

        $this
            ->withToken($reviewer->createToken('me-token')->plainTextToken)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.must_change_password', true);

        $this
            ->withToken($reviewer->createToken('change-password-token')->plainTextToken)
            ->postJson('/api/auth/change-password', [
                'current_password' => 'temporary-password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'new-secure-password',
            ])
            ->assertOk()
            ->assertJsonPath('message', "Mot de passe modifi\u{00E9} avec succ\u{00E8}s.");

        $reviewer = $this->reviewerWhoMustChangePassword(email: 'logout-reviewer@example.test');

        $this
            ->withToken($reviewer->createToken('logout-token')->plainTextToken)
            ->postJson('/api/auth/logout')
            ->assertOk();
    }

    public function test_reviewer_can_access_protected_routes_after_successful_password_change(): void
    {
        $reviewer = $this->reviewerWhoMustChangePassword();

        $token = $reviewer->createToken('api-token')->plainTextToken;

        $this
            ->withToken($token)
            ->postJson('/api/auth/change-password', [
                'current_password' => 'temporary-password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'new-secure-password',
            ])
            ->assertOk();

        $reviewer->refresh();

        $this->assertFalse($reviewer->must_change_password);
        $this->assertTrue(Hash::check('new-secure-password', $reviewer->password));
        $this->assertDatabaseHas('users', [
            'id' => $reviewer->id,
            'must_change_password' => false,
        ]);

        $this
            ->withToken($token)
            ->getJson('/api/reviewer/topics')
            ->assertOk();
    }

    public function test_change_password_fails_with_incorrect_current_password(): void
    {
        $reviewer = $this->reviewerWhoMustChangePassword();

        $this
            ->withToken($reviewer->createToken('api-token')->plainTextToken)
            ->postJson('/api/auth/change-password', [
                'current_password' => 'wrong-password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'new-secure-password',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Le mot de passe actuel est incorrect.');
    }

    public function test_chair_without_required_password_change_is_not_blocked(): void
    {
        [, , $token] = $this->conferenceForChair(status: Conference::STATUS_VALIDATED);

        $this
            ->withToken($token)
            ->getJson('/api/chair/dashboard')
            ->assertOk();
    }

    public function test_login_response_contains_must_change_password_flag(): void
    {
        $this->reviewerWhoMustChangePassword();

        $this
            ->postJson('/api/auth/login', [
                'email' => 'reviewer@example.test',
                'password' => 'temporary-password',
            ])
            ->assertOk()
            ->assertJsonPath('data.user.must_change_password', true);
    }

    private function reviewerWhoMustChangePassword(string $email = 'reviewer@example.test'): User
    {
        return User::factory()->create([
            'email' => $email,
            'role' => User::ROLE_REVIEWER,
            'password' => Hash::make('temporary-password'),
            'must_change_password' => true,
        ]);
    }

    private function conferenceForChair(
        string $email = 'chair@example.test',
        string $status = Conference::STATUS_VALIDATED,
    ): array {
        $chair = User::factory()->create([
            'email' => $email,
            'role' => User::ROLE_CHAIR,
            'must_change_password' => false,
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
