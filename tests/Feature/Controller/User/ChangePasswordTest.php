<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

test('user can change password', function () {
    $user = User::factory()->create(['password' => Hash::make('myOldPassword')]);

    $token = JWTAuth::fromUser($user);

    $currentPassword = 'myOldPassword';
    $newPassword = 'myNewPassword';
    $newPasswordConfirmation = 'myNewPassword';

    $response = $this->postJson('/api/v1/users/' . $user->id . '/change-password', [
        'current_password' => $currentPassword,
        'new_password' => $newPassword,
        'new_password_confirmation' => $newPasswordConfirmation
    ], [
        'Authorization' => 'Bearer ' . $token
    ]);
    $response->assertStatus(200);
    $response->assertJson(['message' => 'Password changed successfully']);
    $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    $this->assertTrue(Auth::attempt(['email' => $user->email, 'password' => $newPassword]));
});


test('user cannot change password with invalid current password', function () {
    $user = User::factory()->create(['password' => Hash::make('myOldPassword')]);

    $token = JWTAuth::fromUser($user);

    $currentPassword = 'myOldPassword123';
    $newPassword = 'myNewPassword';
    $newPasswordConfirmation = 'myNewPassword';

    $response = $this->postJson('/api/v1/users/' . $user->id . '/change-password', [
        'current_password' => $currentPassword,
        'new_password' => $newPassword,
        'new_password_confirmation' => $newPasswordConfirmation
    ], [
        'Authorization' => 'Bearer ' . $token
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['current_password' => 'The current password is incorrect.']);
    $user->refresh();
    expect(Hash::check('myOldPassword', $user->password))->toBeTrue();
    expect(Hash::check($newPassword, $user->password))->toBeFalse();
    expect(Auth::attempt(['email' => $user->email, 'password' => 'myOldPassword']))->toBeTrue();
    expect(Auth::attempt(['email' => $user->email, 'password' => $currentPassword]))->toBeFalse();
});

test('user cannot change password with new password and new password confirmation not match', function () {
    $user = User::factory()->create(['password' => Hash::make('myOldPassword')]);

    $token = JWTAuth::fromUser($user);

    $currentPassword = 'myOldPassword';
    $newPassword = 'myNewPassword';
    $newPasswordConfirmation = 'myNewPassword123';

    $response = $this->postJson('/api/v1/users/' . $user->id . '/change-password', [
        'current_password' => $currentPassword,
        'new_password' => $newPassword,
        'new_password_confirmation' => $newPasswordConfirmation
    ], [
        'Authorization' => 'Bearer ' . $token
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['new_password' => 'The new password and new password confirmation must match.']);
    $user->refresh();
    expect(Hash::check('myOldPassword', $user->password))->toBeTrue();
    expect(Hash::check($newPassword, $user->password))->toBeFalse();

    expect(Auth::attempt(['email' => $user->email, 'password' => 'myOldPassword']))->toBeTrue();
    expect(Auth::attempt(['email' => $user->email, 'password' => $newPassword]))->toBeFalse();
    expect(Auth::attempt(['email' => $user->email, 'password' => $newPasswordConfirmation]))->toBeFalse();
});

test('user cannot change password with new password that is too short', function () {
    $user = User::factory()->create(['password' => Hash::make('myOldPassword')]);

    $token = JWTAuth::fromUser($user);

    $currentPassword = 'myOldPassword';
    $newPassword = '123456';
    $newPasswordConfirmation = '123456';

    $response = $this->postJson('/api/v1/users/' . $user->id . '/change-password', [
        'current_password' => $currentPassword,
        'new_password' => $newPassword,
        'new_password_confirmation' => $newPasswordConfirmation
    ], [
        'Authorization' => 'Bearer ' . $token
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['new_password' => 'The new password must be at least 10 characters.']);
    $user->refresh();
    expect(Hash::check('myOldPassword', $user->password))->toBeTrue();
    expect(Hash::check($newPassword, $user->password))->toBeFalse();
    expect(Auth::attempt(['email' => $user->email, 'password' => 'myOldPassword']))->toBeTrue();
    expect(Auth::attempt(['email' => $user->email, 'password' => $newPassword]))->toBeFalse();
    expect(Auth::attempt(['email' => $user->email, 'password' => $newPasswordConfirmation]))->toBeFalse();
});
