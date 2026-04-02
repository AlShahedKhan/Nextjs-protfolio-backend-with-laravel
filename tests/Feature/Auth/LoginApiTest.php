<?php

use App\Models\User;

it('allows the seeded demo user to log in and receive a bearer token', function () {
    $this->seed();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'ADMIN@example.com',
        'password' => 'Password123!',
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
                'email' => 'admin@example.com',
            ],
        ])
        ->assertJsonMissingPath('user.password');

    $seededUser = User::query()->firstWhere('email', 'admin@example.com');

    expect($seededUser)->not->toBeNull();
    expect($seededUser->tokens()->pluck('name')->all())->toBe(['nextjs-web']);
});

it('returns the same generic error for unknown emails and wrong passwords', function () {
    $this->seed();

    $unknownUser = $this->postJson('/api/v1/auth/login', [
        'email' => 'unknown@example.com',
        'password' => 'Password123!',
        'device_name' => 'nextjs-web',
    ]);

    $wrongPassword = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.com',
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

    $seededUser = User::query()->firstWhere('email', 'admin@example.com');

    expect($seededUser)->not->toBeNull();
    expect($seededUser->tokens()->count())->toBe(0);
});

it('rotates an existing token when the same device logs in again', function () {
    $this->seed();

    $firstToken = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'Password123!',
        'device_name' => 'nextjs-web',
    ])->assertOk()->json('access_token');

    $secondToken = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'Password123!',
        'device_name' => 'nextjs-web',
    ])->assertOk()->json('access_token');

    $user = User::query()->firstWhere('email', 'admin@example.com');

    expect($firstToken)->not->toBe($secondToken);
    expect($user->fresh()->tokens()->where('name', 'nextjs-web')->count())->toBe(1);
});

it('rate limits repeated failed login attempts', function () {
    $this->seed();

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'WrongPassword123!',
            'device_name' => 'nextjs-web',
        ])->assertStatus(422);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'WrongPassword123!',
        'device_name' => 'nextjs-web',
    ])->assertStatus(429);
});

it('returns the authenticated user for a valid sanctum token', function () {
    $this->seed();

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'Password123!',
        'device_name' => 'nextjs-web',
    ])->assertOk();

    $this->withToken($loginResponse->json('access_token'))
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJson([
            'data' => [
                'email' => 'admin@example.com',
                'name' => 'Portfolio Admin',
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
