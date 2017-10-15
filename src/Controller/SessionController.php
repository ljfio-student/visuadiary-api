<?php

namespace Site\Controller;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class SessionController extends Controller
{

    public function login(Request $request, Response $response, $args) :Response
    {
        $user = $request->withAttribute('user');

        if (isset($user)) {
            return $response->withStatus(400); // Bad request: already signed in
        }

        $body = $request->getParsedBody();

        $database = $this->container->get('database');
        $user     = $database->table('user')
            ->where([
                ['email', '=', $body['email']],
            ])
            ->first();

        if ($user != null && password_verify($body['password'], $user->password)) {
            $token = bin2hex(random_bytes(128));

            $database->table('session')
                ->insert([
                    [
                        'user_id' => $user->id,
                        'token'   => $token,
                    ],
                ]);

            return $response->withHeader('Content-Type', 'application/json')
                ->withBody(json_encode([
                    'token' => $token,
                ]));
        } else {
            return $response->withStatus(401);
        }
    }

    public function register(Request $request, Response $response, $args)
    {
        $user = $request->withAttribute('user');

        if (isset($user)) {
            return $response->withStatus(400); // Bad request: already signed in
        }

        $body = $request->getParsedBody();
    }
}
