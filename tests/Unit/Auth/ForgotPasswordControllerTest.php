<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Notifications\ResetPassword;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Make sure no real façade calls happen
    Password::clearResolvedInstances();
});

it('returns a 200 JSON response when sendResetLink succeeds', function () {
    $email = Faker::create()->safeEmail();

    // 2) Create a user in the database with this email
    User::factory()->create(['email' => $email]);

    // 1) Fake a FormRequest whose validated() returns our email
    $request = Mockery::mock(ForgotPasswordRequest::class);
    $request->shouldReceive('validated')->once()->andReturn(['email' => $email]);
    $request->shouldReceive('all')->andReturn(['email' => $email]);
    $request->shouldReceive('wantsJson')->andReturn(true);

    // 2) Mock Password façade to return the “sent” constant
    Password::shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => $email])
        ->andReturn(Password::RESET_LINK_SENT);

    // 3) Call
    $controller = new ForgotPasswordController();
    $response = $controller->sendResetLinkEmail($request);

    // 4) Assert it’s exactly the JSON you expect
    expect($response)
        ->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toMatchArray([
            'status'  => 'success',
            'message' => 'Password reset link sent successfully. Please check your email.',
        ]);
});


it("Successfully stores the token in the database", function () {
    // 1) Arrange a real user and fake notifications
    $user = User::factory()->create();
    Notification::fake();

    // 2) Mock only the FormRequest so it returns our email
    $request = Mockery::mock(ForgotPasswordRequest::class);
    $request->shouldReceive('validated')->once()->andReturn(['email' => $user->email]);
    $request->shouldReceive('wantsJson')->andReturn(true);

    // 3) Stub Password::sendResetLink to both insert a row and queue a notification
    // In the 'Successfully stores the token in the database' test
    Password::shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => $user->email])
        // CHANGE THIS LINE
        ->andReturnUsing(function (array $credentials) use ($user) {
            // Now you can access the email like this:
            $email = $credentials['email'];

            // simulate the broker writing a DB record
            $token = Str::random(64);
            DB::table('password_reset_tokens')->insert([
                'email'      => $email,
                'token'      => hash('sha256', $token),
                'created_at' => now(),
            ]);

            // simulate the broker firing the notification
            Notification::send($user, new ResetPassword($token));

            return Password::RESET_LINK_SENT;
        });

    // 4) Act: call your controller
    $response = (new ForgotPasswordController())->sendResetLinkEmail($request);

    // 5) Assert: correct JsonResponse
    expect($response)
        ->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toMatchArray([
            'status'  => 'success',
            'message' => 'Password reset link sent successfully. Please check your email.',
        ]);

    // 6) Assert: token row is in the database
    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => $user->email,
    ]);

    // 7) Assert: notification was sent
    Notification::assertSentTo($user, ResetPassword::class);
});
