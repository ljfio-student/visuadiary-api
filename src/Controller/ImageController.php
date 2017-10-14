<?php

namespace Site\Controller;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ImageController extends Controller
{
    public function upload(Request $request, Response $response): Response
    {
        return $response;
    }
}
