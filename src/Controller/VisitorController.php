<?php

namespace Site\Controller;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class VisitorController extends Controller
{
    // /visitor/{id}
    public function get(Request $request, Response $response, $args): Response
    {
        $user = $request->getAttribute('user');

        if (!isset($user)) {
            return $response->withStatus(401);
        }

        $visitor_id = $args['id'];

        $database = $this->container->get('database');
        $visitor = $database->table('visitor')
            ->where([
                ['id', '=', $visitor_id]
                ['user_id', '=', $user->id]
            ])
            ->first();

        if ($visitor != null) {
            return $response->withHeader('Content-Type', 'application/json')
                ->withBody(json_encode([
                    '' =>
                ]));
        } else {
            return $response->withStatus(404);
        }
    }

    // /visitor
    public function add(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        if (!isset($user)) {
            return $response->withStatus(401);
        }

        $data = $request->getParsedBody();


    }

    // /visitor/{id}
    public function update(Request $request, Response $response, $args): Response
    {
        $user = $request->getAttribute('user');

        if (!isset($user)) {
            return $response->withStatus(401);
        }

        $visitor_id = $args['id'];
        $data = $request->getParsedBody();
    }

    // /visitor/{id}
    public function remove(Request $request, Response $response, $args): Response
    {
        $user = $request->getAttribute('user');

        if (!isset($user)) {
            return $response->withStatus(401);
        }

        $database = $this->container->get('database');
        $exists = $database->table('visitor')
            ->where([
                ['id', '=', $args['id']],
                ['user_id', '=', $user->id],
            ])
            ->exists();

        if ($exists) {
            $database->table('visitor')
                ->where([
                    ['id', '=', $args['id']],
                ])
                ->delete();
        }
    }
}
