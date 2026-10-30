<?php

namespace Tests\Feature\Security;

use Illuminate\Routing\Route as RoutingRoute;
use Tests\TestCase;

class ProtectedRoutesTest extends TestCase
{
    public function test_guest_cannot_receive_success_responses_from_authenticated_routes(): void
    {
        $protectedRoutes = collect(app('router')->getRoutes())
            ->filter(fn (RoutingRoute $route) =>
                in_array('auth', $route->middleware(), true)
            );

        $this->assertNotEmpty(
            $protectedRoutes,
            'The route collection should include authenticated routes.'
        );

        foreach ($protectedRoutes as $route) {
            $uri = preg_replace('/\{[^}]+\??\}/', '1', $route->uri());
            $methods = array_values(array_diff(
                $route->methods(),
                ['HEAD', 'OPTIONS']
            ));

            if ($methods === []) {
                continue;
            }

            $method = in_array('GET', $methods, true)
                ? 'GET'
                : $methods[0];
            $response = $this->call($method, '/'.$uri, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);

            $this->assertContains(
                $response->getStatusCode(),
                [302, 401, 403, 404],
                "{$method} /{$uri} must not be accessible to a guest."
            );
        }
    }
}
