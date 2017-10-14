<?php

namespace Site\Controller;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ImageController extends Controller
{
    public function visitor(Request $request, Response $response, $args): Response
    {
        $user = $request->getAttribute('user');

        if (!isset($user)) {
            $database = $this->container->get('dynamodb');

            // 1. Check visitor exists and get collection/face id
            $databaseResult = $database->getItem([
                'TableName' => 'Visitor',
                'Key'       => [
                    'Id' => [
                        'S' => $args['id'],
                    ],
                ],
            ]);

            if ($databaseResult != null) {
                $body = $request->getBody();
                // TODO: Check that the content type is legit

                $storage = $this->container->get('s3');

                $key = 'ekslsefef'; // TODO: Unique key name for object

                // 2. Upload the image into S3
                $storageResult = $storage->putObject([
                    'Body'   => $body,
                    'Key'    => $key,
                    'Bucket' => $user->Item->Bucket['S'], // TODO: Check this
                ]);

                if ($storageResult != null) {
                    // TODO: 3. Remove any instance of face id (if any)
                    $imaging = $this->container->get('rekognition');

                    // 4. Add the image to the collection (specified by profile)
                    $imagingResult = $imaging->indexFaces([
                        'CollectionId' => $user->Item->CollectionId['S'], // TODO: Check this!
                        'Image'        => [
                            'S3Object' => [
                                'Bucket' => $user->Item->Bucket['S'],
                                'Name'   => $key,
                            ],
                        ],
                    ]);

                    if ($imagingResult != null) {
                        // 5. Put face id on the visitor data
                        $database->updateItem([
                            'ExpressionAttributeNames'  => [
                                '#K' => 'S3Key',
                            ],
                            'ExpressionAttributeValues' => [
                                ':k' => [
                                    'S' => $key,
                                ],
                            ],
                            'Key'                       => [
                                'Id' => [
                                    'S' => $args['id'],
                                ],
                            ],
                            'ReturnValues'              => 'ALL_NEW',
                            'TableName'                 => 'Visitor',
                            'UpdateExpression'          => 'SET #K = :k',
                        ]);

                        return $response;
                    } else {
                        return $response->withStatus(500); // We could not index the image
                    }
                } else {
                    return $response->withStatus(500); // We could not store the data
                }
            } else {
                return $response->withStatus(404); // We cannot find the visitor
            }
        } else {
            return $response->withStatus(401); // TODO: Check this is correct
        }
    }

    public function profile(Request $request, Response $response, $args): Response
    {

    }
}
