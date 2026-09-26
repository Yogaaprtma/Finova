<?php

namespace App\Http\Middleware;

use App\Services\Messaging\Adapters\FonnteAdapter;
use App\Services\Messaging\Contracts\MessagingProviderInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWhatsAppSignature
{
    public function __construct(
        protected ?MessagingProviderInterface $provider = null
    ) {
        $this->provider = $provider ?? new FonnteAdapter();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->provider->verifyWebhook($request)) {
            return response()->json([
                'error' => 'Invalid or unauthorized webhook signature',
            ], 401);
        }

        return $next($request);
    }
}

