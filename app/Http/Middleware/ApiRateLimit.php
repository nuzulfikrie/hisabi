<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApiRateLimit
{
    /**
     * The rate limiter instance.
     */
    private RateLimiter $limiter;

    /**
     * Maximum number of requests allowed per window.
     */
    private int $maxAttempts = 10;

    /**
     * Time window in minutes.
     */
    private int $decayMinutes = 1;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $this->maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);

            Log::warning('API rate limit exceeded', [
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'path' => $request->path(),
                'retry_after' => $retryAfter,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $this->maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->getTimestamp(),
            ]);
        }

        $this->limiter->hit($key, $this->decayMinutes * 60);

        $response = $next($request);

        // Add rate limit headers to successful responses
        if ($response instanceof Response) {
            $remaining = $this->maxAttempts - $this->limiter->attempts($key);
            $response->headers->set('X-RateLimit-Limit', (string) $this->maxAttempts);
            $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remaining));
        }

        return $response;
    }

    /**
     * Resolve request signature for rate limiting.
     */
    private function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();

        if ($user) {
            return 'api_rate_limit:user:' . $user->id;
        }

        return 'api_rate_limit:ip:' . $request->ip();
    }
}
