<?php
/**
 * Middleware Interface
 * Defines the contract for all middleware implementations
 */
interface MiddlewareInterface
{
    /**
     * Handle the middleware request
     *
     * @param array $params Route parameters
     * @param callable $next Next middleware in chain
     * @return bool True if request should continue, false if blocked
     */
    public function handle($params, $next);
}