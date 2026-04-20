<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\RedirectGuestToLoginOptions;
use App\Livewire\Actions\Logout;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Carbon\CarbonImmutable;

test('profile validation rules include unique email ignore support', function () {
    $rules = new class
    {
        use ProfileValidationRules;

        public function expose(?int $userId = null): array
        {
            return $this->profileRules($userId);
        }
    };

    expect($rules->expose())->toHaveKeys(['name', 'email'])
        ->and($rules->expose(5)['email'][4])->toBeInstanceOf(Rule::unique(User::class)->ignore(5)::class);
});

test('password validation rules include current password rule', function () {
    $rules = new class
    {
        use PasswordValidationRules;

        public function exposeCurrent(): array
        {
            return $this->currentPasswordRules();
        }
    };

    expect($rules->exposeCurrent())->toBe(['required', 'string', 'current_password']);
});

test('authenticate middleware redirects browser requests to login only', function () {
    $middleware = new Authenticate(app('auth'));
    $method = new ReflectionMethod($middleware, 'redirectTo');
    $method->setAccessible(true);

    $browserRequest = Request::create('/private');
    $jsonRequest = Request::create('/private', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

    expect($method->invoke($middleware, $browserRequest))->toBe(route('login'))
        ->and($method->invoke($middleware, $jsonRequest))->toBeNull();
});

test('redirect guest middleware redirects guests and lets users continue', function () {
    Route::get('/middleware-probe', fn () => 'allowed')->middleware(RedirectGuestToLoginOptions::class);

    $this->get('/middleware-probe')
        ->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get('/middleware-probe')
        ->assertOk()
        ->assertSee('allowed');
});

test('admin middleware redirects guests before role checks', function () {
    Route::get('/admin-middleware-probe', fn () => 'allowed')->middleware(AdminMiddleware::class);

    $this->get('/admin-middleware-probe')
        ->assertRedirect('/login');
});

test('livewire logout action logs out and invalidates the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = app(Logout::class)();

    expect($response->getTargetUrl())->toBe(url('/'));
    $this->assertGuest();
});

test('app service provider configures local forwarded host defaults', function () {
    $this->app['env'] = 'local';
    app()->instance('request', Request::create('/', 'GET', [], [], [], ['HTTP_X_FORWARDED_HOST' => 'proxy.test']));

    URL::shouldReceive('forceRootUrl')->once()->with('https://proxy.test');
    URL::shouldReceive('forceScheme')->once()->with('https');
    Date::shouldReceive('use')->once()->with(CarbonImmutable::class);

    $provider = new AppServiceProvider($this->app);
    $method = new ReflectionMethod($provider, 'configureDefaults');
    $method->setAccessible(true);
    $method->invoke($provider);

    expect(Password::default()->appliedRules()['min'])->toBe(8);
});

test('app service provider configures production password defaults', function () {
    $this->app['env'] = 'production';
    app()->instance('request', Request::create('/', 'GET'));

    Date::shouldReceive('use')->once()->with(CarbonImmutable::class);

    $provider = new AppServiceProvider($this->app);
    $method = new ReflectionMethod($provider, 'configureDefaults');
    $method->setAccessible(true);
    $method->invoke($provider);

    expect(Password::default()->appliedRules())->toBe([
        'min' => 12,
        'max' => null,
        'mixedCase' => true,
        'letters' => true,
        'numbers' => true,
        'symbols' => true,
        'uncompromised' => true,
        'compromisedThreshold' => 0,
        'customRules' => [],
    ]);
});

test('fortify registers the two factor limiter session key callback', function () {
    $limiter = RateLimiter::limiter('two-factor');
    $request = Request::create('/two-factor', 'POST');
    $request->setLaravelSession(app('session')->driver());
    $request->session()->put('login.id', 'user-login-id');

    $limit = $limiter($request);

    expect($limit->maxAttempts)->toBe(5)
        ->and($limit->key)->toBe('user-login-id');
});
