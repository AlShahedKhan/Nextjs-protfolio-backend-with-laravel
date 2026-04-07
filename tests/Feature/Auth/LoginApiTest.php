<?php

use App\Models\User;
use Database\Seeders\DemoUserSeeder;

it('allows the seeded demo user to log in and receive a bearer token', function () {
    $this->seed();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => strtoupper(DemoUserSeeder::ADMIN_EMAIL),
        'password' => DemoUserSeeder::ADMIN_PASSWORD,
        'device_name' => 'nextjs-web',
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'token_type',
            'access_token',
            'expires_at',
            'user' => ['id', 'name', 'email', 'email_verified_at', 'created_at'],
        ])
        ->assertJson([
            'token_type' => 'Bearer',
            'user' => [
                'email' => DemoUserSeeder::ADMIN_EMAIL,
            ],
        ])
        ->assertJsonMissingPath('user.password');

    $seededUser = User::query()->firstWhere('email', DemoUserSeeder::ADMIN_EMAIL);

    expect($seededUser)->not->toBeNull();
    expect($seededUser->tokens()->pluck('name')->all())->toBe(['nextjs-web']);
});

it('auto-detects the device name from the request when it is not provided', function () {
    $this->seed();

    $response = $this
        ->withHeader('User-Agent', 'Next.js Portfolio Frontend')
        ->postJson('/api/v1/auth/login', [
            'email' => DemoUserSeeder::ADMIN_EMAIL,
            'password' => DemoUserSeeder::ADMIN_PASSWORD,
        ]);

    $response->assertOk();

    $seededUser = User::query()->firstWhere('email', DemoUserSeeder::ADMIN_EMAIL);

    expect($seededUser)->not->toBeNull();
    expect($seededUser->tokens()->pluck('name')->all())->toBe(['Next.js Portfolio Frontend']);
});

it('returns the correct method details when login is called with the wrong method', function () {
    $this->getJson('/api/v1/auth/login')
        ->assertStatus(405)
        ->assertHeader('Allow', 'POST')
        ->assertJson([
            'message' => 'The GET method is not supported for this route. Use POST.',
            'wrong_method' => 'GET',
            'correct_method' => 'POST',
            'allowed_methods' => ['POST'],
        ]);
});

it('returns the preferred method when a protected route is called with the wrong method', function () {
    $this->postJson('/api/v1/auth/me')
        ->assertStatus(405)
        ->assertHeader('Allow', 'GET, HEAD')
        ->assertJson([
            'message' => 'The POST method is not supported for this route. Use GET.',
            'wrong_method' => 'POST',
            'correct_method' => 'GET',
            'allowed_methods' => ['GET', 'HEAD'],
        ]);
});

it('returns the same generic error for unknown emails and wrong passwords', function () {
    $this->seed();

    $unknownUser = $this->postJson('/api/v1/auth/login', [
        'email' => 'unknown@example.com',
        'password' => 'Password123!',
        'device_name' => 'nextjs-web',
    ]);

    $wrongPassword = $this->postJson('/api/v1/auth/login', [
        'email' => DemoUserSeeder::ADMIN_EMAIL,
        'password' => 'WrongPassword123!',
        'device_name' => 'nextjs-web',
    ]);

    $unknownUser
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    $wrongPassword
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    expect($unknownUser->json('errors.email.0'))
        ->toBe($wrongPassword->json('errors.email.0'))
        ->toBe(trans('auth.failed'));

    $seededUser = User::query()->firstWhere('email', DemoUserSeeder::ADMIN_EMAIL);

    expect($seededUser)->not->toBeNull();
    expect($seededUser->tokens()->count())->toBe(0);
});

it('rotates an existing token when the same device logs in again', function () {
    $this->seed();

    $firstToken = $this->postJson('/api/v1/auth/login', [
        'email' => DemoUserSeeder::ADMIN_EMAIL,
        'password' => DemoUserSeeder::ADMIN_PASSWORD,
        'device_name' => 'nextjs-web',
    ])->assertOk()->json('access_token');

    $secondToken = $this->postJson('/api/v1/auth/login', [
        'email' => DemoUserSeeder::ADMIN_EMAIL,
        'password' => DemoUserSeeder::ADMIN_PASSWORD,
        'device_name' => 'nextjs-web',
    ])->assertOk()->json('access_token');

    $user = User::query()->firstWhere('email', DemoUserSeeder::ADMIN_EMAIL);

    expect($firstToken)->not->toBe($secondToken);
    expect($user->fresh()->tokens()->where('name', 'nextjs-web')->count())->toBe(1);
});

it('rate limits repeated failed login attempts', function () {
    $this->seed();

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/login', [
            'email' => DemoUserSeeder::ADMIN_EMAIL,
            'password' => 'WrongPassword123!',
            'device_name' => 'nextjs-web',
        ])->assertStatus(422);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => DemoUserSeeder::ADMIN_EMAIL,
        'password' => 'WrongPassword123!',
        'device_name' => 'nextjs-web',
    ])->assertStatus(429);
});

it('returns the authenticated user for a valid sanctum token', function () {
    $this->seed();

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => DemoUserSeeder::ADMIN_EMAIL,
        'password' => DemoUserSeeder::ADMIN_PASSWORD,
        'device_name' => 'nextjs-web',
    ])->assertOk();

    $this->withToken($loginResponse->json('access_token'))
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJson([
            'data' => [
                'email' => DemoUserSeeder::ADMIN_EMAIL,
                'name' => DemoUserSeeder::ADMIN_NAME,
            ],
        ]);
});

it('revokes the current token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('nextjs-web', ['access-api'], now()->addWeek());

    $this->withToken($token->plainTextToken)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    expect($user->fresh()->tokens()->count())->toBe(0);
});
