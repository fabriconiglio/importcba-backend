<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TokenRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts())) {
            return $this->buildResponse($key);
        }

        RateLimiter::hit($key, $this->decayMinutes() * 60);

        $response = $next($request);

        $response = $this->addHeaders(
            $response, $this->maxAttempts(),
            $this->calculateRemainingAttempts($key)
        );
        
        return $response;
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return sha1($user->getAuthIdentifier());
        }

        return sha1($request->ip());
    }

    /**
     * Create a 'too many attempts' response.
     */
    protected function buildResponse(string $key): Response
    {
        $retryAfter = $this->getTimeUntilNextAttempt($key);

        $response = response()->json([
            'message' => 'Too many authentication attempts. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429);
        
        $response->headers->set('Retry-After', $retryAfter);
        $response->headers->set('X-RateLimit-Reset', $this->availableAt($retryAfter));
        
        return $response;
    }

    /**
     * Add the limit header information to the given response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remainingAttempts);
        
        return $response;
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key): int
    {
        return RateLimiter::remaining($key, $this->maxAttempts());
    }

    /**
     * Get the number of seconds until the next attempt.
     */
    protected function getTimeUntilNextAttempt(string $key): int
    {
        return RateLimiter::availableIn($key);
    }

    /**
     * Get the maximum number of attempts for the rate limiter.
     */
    protected function maxAttempts(): int
    {
        return env('TOKEN_RATE_LIMIT_MAX_ATTEMPTS', 60);
    }

    /**
     * Get the number of minutes to decay the rate limiter.
     */
    protected function decayMinutes(): int
    {
        return env('TOKEN_RATE_LIMIT_DECAY_MINUTES', 1);
    }

    /**
     * Get the "available at" UNIX timestamp.
     */
    protected function availableAt(int $delay): int
    {
        return now()->addSeconds($delay)->getTimestamp();
    }
} 