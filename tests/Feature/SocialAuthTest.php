<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SocialAccount;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_redirects_to_supported_oauth_provider(): void
    {
        $providerMock = Mockery::mock(Provider::class);
        $providerMock->shouldReceive('redirect')
            ->once()
            ->andReturn(new SymfonyRedirectResponse('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')
            ->with('google')
            ->once()
            ->andReturn($providerMock);

        $response = $this->get(route('social.redirect', ['provider' => 'google']));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_it_returns_404_for_unsupported_provider(): void
    {
        $response = $this->get('/auth/unsupported/redirect');

        $response->assertNotFound();
    }

    public function test_it_creates_new_user_and_seeds_categories_on_callback(): void
    {
        $socialUser = Mockery::mock(SocialUserContract::class);
        $socialUser->shouldReceive('getId')->andReturn('google-uid-1001');
        $socialUser->shouldReceive('getName')->andReturn('Ahmad Finova');
        $socialUser->shouldReceive('getNickname')->andReturn(null);
        $socialUser->shouldReceive('getEmail')->andReturn('ahmad@example.com');
        $socialUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');

        $providerMock = Mockery::mock(Provider::class);
        $providerMock->shouldReceive('user')
            ->once()
            ->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->once()
            ->andReturn($providerMock);

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'ahmad@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Ahmad Finova', $user->name);

        // Verify social account was attached
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-uid-1001',
            'avatar' => 'https://example.com/avatar.png',
        ]);

        // Verify default categories were seeded
        $this->assertTrue(Category::where('user_id', $user->id)->exists());
    }

    public function test_it_links_social_account_to_existing_user_with_same_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'sarah@example.com',
            'name' => 'Sarah Existing',
        ]);

        $socialUser = Mockery::mock(SocialUserContract::class);
        $socialUser->shouldReceive('getId')->andReturn('apple-sub-999');
        $socialUser->shouldReceive('getName')->andReturn('Sarah Existing');
        $socialUser->shouldReceive('getNickname')->andReturn(null);
        $socialUser->shouldReceive('getEmail')->andReturn('sarah@example.com');
        $socialUser->shouldReceive('getAvatar')->andReturn(null);

        $providerMock = Mockery::mock(Provider::class);
        $providerMock->shouldReceive('user')
            ->once()
            ->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->with('apple')
            ->once()
            ->andReturn($providerMock);

        $response = $this->get(route('social.callback', ['provider' => 'apple']));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($existingUser);

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $existingUser->id,
            'provider' => 'apple',
            'provider_id' => 'apple-sub-999',
        ]);
    }

    public function test_it_logs_in_existing_social_account(): void
    {
        $user = User::factory()->create();
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'facebook',
            'provider_id' => 'fb-id-555',
            'avatar' => 'https://facebook.com/avatar.jpg',
        ]);

        $socialUser = Mockery::mock(SocialUserContract::class);
        $socialUser->shouldReceive('getId')->andReturn('fb-id-555');
        $socialUser->shouldReceive('getAvatar')->andReturn('https://facebook.com/avatar_new.jpg');

        $providerMock = Mockery::mock(Provider::class);
        $providerMock->shouldReceive('user')
            ->once()
            ->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->with('facebook')
            ->once()
            ->andReturn($providerMock);

        $response = $this->get(route('social.callback', ['provider' => 'facebook']));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'facebook',
            'provider_id' => 'fb-id-555',
            'avatar' => 'https://facebook.com/avatar_new.jpg',
        ]);
    }

    public function test_it_handles_callback_exceptions_gracefully(): void
    {
        $providerMock = Mockery::mock(Provider::class);
        $providerMock->shouldReceive('user')
            ->once()
            ->andThrow(new Exception('Access denied by user'));

        Socialite::shouldReceive('driver')
            ->with('google')
            ->once()
            ->andReturn($providerMock);

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertGuest();
    }
}
