<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Notification::fake();
});

test("A valid user can request a password reset link", function () {
    $user = User::factory()->create()->fresh();

    $this->postJson('/api/v1/auth/password/email', ['email' => $user->email])
        ->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Password reset link sent successfully. Please check your email.'
        ]);

    Notification::assertSentTo($user, ResetPassword::class);
    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => $user->email
    ]);
});

test('Email not found', function () {
    $email = Faker::create()->safeEmail();

    $this->postJson('/api/v1/auth/password/email', ['email' => $email])
        ->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Password reset link sent successfully. Please check your email.'
        ]);

    Notification::assertNothingSent();
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $email]);
});
