<?php

use App\Models\User;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use Illuminate\Support\Facades\Hash;


function validUserData(): array {
    return [
        'firstName'    => 'John',
        'lastName'     => 'Doe',
        'gender'       => 'male',
        'country'      => 'USA',
        'city'         => 'New York',
        'birthday'        => '2001-11-20',
        'userName'     => 'johndoe',
        'email'        => 'john@example.com',
        'phone'        => '1234567890',
        'password'     => 'secret123@12045',
        'accountType'  => '1',
        'status'       =>  '1',
    ];
}


it("creates a new account with valid data", function () {
    $data = validUserData();

    $response = $this->postJson('/api/v1/auth/register', $data);

    $response->assertCreated(201)
             ->assertJsonStructure([
                 'message',
                 'user' => [
                     'id', 
                     'firstName',
                     'lastName',
                     'gender',
                     'country', 
                     'city',
                     'birthday',
                     'userName', 
                     'email',
                     'phone',
                     'accountType',
                     'status',
                 ]
             ])
             ->assertJsonFragment([
                 'message' => 'User created successfully',
             ]);

    $this->assertDatabaseHas('users', [
        'firstName'    => $data['firstName'],
        'lastName'     => $data['lastName'],
        'gender'       => $data['gender'],
        'country'      => $data['country'],
        'city'         => $data['city'],
        'birthday'        => $data['birthday'],
        'userName'     => $data['userName'],
        'email'        => $data['email'],
        'phone'        => $data['phone'],
        'accountType'  => $data['accountType'],
        'status'       => $data['status'],
    ]);

    $user = \App\Models\User::where('email', $data['email'])->first();
    expect(\Illuminate\Support\Facades\Hash::check($data['password'], $user->password))->toBeTrue();
});

it('fails registration when required fields are missing', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertStatus(422)
             ->assertJsonValidationErrors([
                'firstName',
                'lastName',
                'userName',
                'email',
                'password',
                'gender',
                'country',
                'city',
                'birthday',
                'phone',
                'accountType',
                'status',
             ]);
});
it('registration fails with duplicate email and username', function () {
    // First, create an existing user
    User::factory()->create([
        'email' => 'duplicate@email.com',
        'userName' => 'duplicateuser',
    ]);

    // Try to register with the same email and username
    $response = $this->postJson('/api/v1/auth/register', [
          'firstName'    => 'John',
           'lastName'     => 'Doe',
           'gender'       => 'male',
            'country'      => 'USA',
            'city'         => 'New York',
            'birthday'      => '1990-01-01',
            'userName'     => 'duplicateuser',
            'email'        => 'duplicate@email.com',
            'phone'        => '1234567890',
            'password'     => 'duplicateuser',
            'accountType'  => '120',
            'status'       => '120',
        // include all other required fields
    ]);

    // Expect a 422 Unprocessable Entity
    $response->assertStatus(422);

    // Check that both fields triggered validation errors
    $response->assertJsonValidationErrors(['email', 'userName']);
});

/* it('returns a JWT token with valid credentials using email or username', function () {
    // 🔹 ARRANGE
    $user = User::create([
        'firstName'    => 'John',
        'lastName'     => 'Doe',
        'gender'       => 'male',
        'country'      => 'USA',
        'city'         => 'New York',
        'birthday'     => '1990-01-01',
        'userName'     => 'johndoe',
        'email'        => 'john@example.com',
        'phone'        => '1234567890',
        'password'     => Hash::make('password123@'),
        'accountType'  => '123',
        'status'       => '123',
    ]);

    // 🔹 ACT & ASSERT - Connexion avec email
    $responseEmail = $this->json('POST', '/api/v1/auth/login', [
        'login'    => 'john@example.com',
        'password' => 'password123@',
    ])->du
     // 👈 This will show the full JSON returned


    // 🔹 ACT & ASSERT - Connexion avec username
    $responseUsername= $this->json('POST', '/api/v1/auth/login', [
        'login'    => 'johndoe',
        'password' => 'password123@',
    ]);
    $responseUsername->dump(); // 👈 This will show the full JSON returned


   $responseUsername->dump(); // Affiche la réponse JSON
dd($responseUsername->json()); // Arrête l'exécution et affiche le contenu

}); */


it('fails to login with invalid credentials', function () {
    // Optional: create a valid user, but we'll use wrong credentials
    User::create([
        'firstName'    => 'John',
        'lastName'     => 'Doe',
        'gender'       => 'male',
        'country'      => 'USA',
        'city'         => 'New York',
        'birthday'     => '1990-01-01',
        'userName'     => 'johndoe',
        'email' => 'valid@email.com',
        'phone'        => '1234567890',
        'password' => Hash::make('wrongpassword'), // Hash it like Laravel does
        'accountType'  => '123',
        'status'       => '123',
        // Include other required fields
    ]);

    // Try login with wrong password
    $response = $this->json('POST','/api/v1/auth/login', [
        'login' => 'valid@email.com',
        'password' => bcrypt('password123'),
    ]);

     $response->assertStatus(401)
             ->assertJsonMissing(['access_token']);

    $response=$this->json('POST','/api/v1/auth/login',[
        'login'=>'wrongjohndoe',
        'password'=>bcrypt('password123'),
    ]);


    $response->assertStatus(401)
             ->assertJsonMissing(['access_token']);
});





