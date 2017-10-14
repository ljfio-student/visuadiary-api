<?php

namespace Site\Middleware;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class AuthenticationMiddleware
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, $next): Response
    {
        $authentication_key = $request->getHeader('Authentication');

        $database = $this->container->get('database');

        $user = $database->table('session')
            ->where([
                ['token', '=', $authentication_key]
            ])
            ->join('user', 'user.id', '=', 'session.user_id')
            ->select('user.*')
            ->first();

        if ($user != null) {
            $request = $request->withAttribute('user', $user);
        }

        return $next($request, $response);
    }
}
