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

        $client = $this->container->get('dynamodb');

        $user = $client->getItem([
            'TableName' => 'User',
            'KeyConditionExpression' => 'contains(Sessions, :s)',
            'ExpressionAttributeValues' => [
                ':s' => $authentication_key,
            ],
        ]);

        if ($user != null) {
            $request->withAttribute('user', $user);
        }

        return $next($request, $response);
    }
}
