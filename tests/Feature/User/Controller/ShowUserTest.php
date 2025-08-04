<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;

test("a user successfully retrieving their own profile", function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/users/' . $user->id);
    $response
        ->assertJson(
            fn(AssertableJson $json) =>
            $json->where('message', 'Data retrived with scuess')
                ->where('id', $user->id)
                ->where('firstName', $user->firstName)
                ->where('lastName', $user->lastName)
                ->where('userName', $user->userName)
                ->where('email',  $user->email)
                ->etc()
        )
        ->assertStatus(200);
});



test('user can not see other users data', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA);
    $response = $this->getJson('/api/v1/users/' . $userB->id);
    $response->assertJsonMissing([
        'id' => $userB->id,
        'firstName' => $userB->firstName,
        'email' => $userB->email,
    ])
        ->assertStatus(403);
});
