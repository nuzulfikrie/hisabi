<?php

namespace App\Services;

class QuerySessionService
{
    protected string $sessionKey;

    public function __construct()
    {
        if (! is_null(request()->route())) {
            $this->sessionKey = 'query_string_'.request()->route()->getName();
        }
    }

    /**
     * Store current query string in session.
     */
    public function store(): void
    {
        session([$this->sessionKey => request()->query()]);
    }

    /**
     * Get back URL with saved query string.
     */
    public function getBackUrl(string $routeName, array $extraParams = []): string
    {
        $key = 'query_string_'.$routeName;
        $query = session($key, []);
        $query = array_merge($query, $extraParams);

        return route($routeName).'?'.http_build_query($query);
    }

    /**
     * Get stored query for a route.
     */
    public function getStoredQuery(string $routeName): array
    {
        return session('query_string_'.$routeName, []);
    }
}
