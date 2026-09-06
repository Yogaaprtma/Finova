<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '0');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_webhook_rate_limiting_is_enforced(): void
    {
        config(['finance.whatsapp.providers.fonnte.webhook_secret' => null]);

        // Send requests up to rate limit
        for ($i = 1; $i <= 120; $i++) {
            $res = $this->postJson('/api/webhooks/whatsapp', [
                'sender' => '6281234567890',
                'message' => "test rate limit {$i}",
                'id' => "msg-rate-{$i}",
            ]);
            $res->assertStatus(200);
        }

        // 121st request should be throttled with HTTP 429 Too Many Requests
        $response = $this->postJson('/api/webhooks/whatsapp', [
            'sender' => '6281234567890',
            'message' => 'test rate limit 121',
            'id' => 'msg-rate-121',
        ]);

        $response->assertStatus(429);
    }
}
