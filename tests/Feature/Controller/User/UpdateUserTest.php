<?php

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

test('authenticated user can successfully update several of their own profile fields', function () {
    // ARRANGE
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $newFirstName = 'Aoui';
    $newLastName = 'Rachid';
    $newCity = 'Casablanca';

    $response = $this->putJson(
        '/api/v1/users/'.$user->id,
        [
            'firstName' => $newFirstName,
            'lastName' => $newLastName,
            'city' => $newCity,
        ],
        [
            'Authorization' => 'Bearer '.$token,
        ]
    );

    // ASSERT
    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'User updated successfully',
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'firstName' => $newFirstName,
        'lastName' => $newLastName,
        'city' => $newCity,
    ]);
});

it('Fail to update user profile with invalid data', function () {
    $userToUpdate = User::factory()->create();
    $otherUser = User::factory()->create(['userName' => 'otherUser', 'phone' => '0766051861']);

    $token = JWTAuth::fromUser($userToUpdate);

    $originalUserName = $userToUpdate->userName;
    $originalEmail = $userToUpdate->email;

    $response = $this->putJson(
        '/api/v1/users/'.$userToUpdate->id,
        [
            'userName' => 'otherUser',
            'email' => 'invalidEmail',
        ],
        [
            'Authorization' => 'Bearer '.$token,
        ]
    );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'userName' => 'The user name has already been taken.',
        'email' => 'The email field must be a valid email address.',
    ]);

    $userToUpdate->refresh();
    expect($userToUpdate->userName)->toBe($originalUserName);
    expect($userToUpdate->email)->toBe($originalEmail);
});

test(' user cannot update a restricted field', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);
    $newData = [
        'id' => '2',
        'firstName' => 'Aoui',
        'lastName' => 'Rachid',
        'city' => 'Casablanca',
    ];
    $originalId = $user->id;

    $response = $this->putJson(
        '/api/v1/users/'.$user->id,
        $newData,
        [
            'Authorization' => 'Bearer '.$token,
        ]
    );
    $user->refresh();
    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'User updated successfully',
    ]);
    expect($user->id)->toBe($originalId);
    expect($user->firstName)->toBe($newData['firstName']);
    expect($user->lastName)->toBe($newData['lastName']);
    expect($user->city)->toBe($newData['city']);
});
