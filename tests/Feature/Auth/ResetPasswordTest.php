<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

test('Successful Password Update', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $newPassword = 'newPassword123!';
    $password_confirmation = $newPassword;

    $this->postJson('/api/v1/auth/password/reset', ['email' => $user->email, 'token' => $token, 'password' => $newPassword, 'password_confirmation' => (string) $password_confirmation])
        ->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Password has been reset successfully. Please log in with your new password.',
        ]);

    $user->refresh();
    expect(Hash::check($newPassword, $user->password))->toBeTrue();
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
});

it('has an invalid token', function () {
    $user = User::factory()->create();
    $token = 'ggetyss4887#sdasd';
    $newPassword = 'newPassword123!';
    $password_confirmation = $newPassword;

    $this->postJson('/api/v1/auth/password/reset', ['email' => $user->email, 'token' => $token, 'password' => $newPassword, 'password_confirmation' => (string) $password_confirmation])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'email' => trans('passwords.token'), // This will check for a specific validation error
        ]);
    $user->refresh();
    expect(Hash::check($newPassword, $user->password))->toBeFalse();
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
});
