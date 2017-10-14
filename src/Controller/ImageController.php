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
                    'Id' => ['S' => $args['id']],
                ],
            ]);

            if ($databaseResult != null) {
                $body = $request->getBody();
                // TODO: Check that the content type is legit

                $storage = $this->container->get('s3');

                $key = uniqid("v_"); // Unique key name for object

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
                                ':k' => ['S' => $key],
                            ],
                            'Key'                       => [
                                'Id' => ['S' => $args['id']],
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
        $user = $request->getAttribute('user');

        if (!isset($user)) {
            $body = $request->getBody();
            // TODO: Check that the content type is legit

            $storage = $this->container->get('s3');

            $key = uniqid("p_"); // Unique key name for object

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
                            '#F' => 'FaceId',
                        ],
                        'ExpressionAttributeValues' => [
                            ':k' => ['S' => $key],
                            ':f' => ['S' => $imagingResult->FaceRecords[0]->Face->FaceId],
                        ],
                        'Key'                       => [
                            'Id' => ['S' => $args['id']],
                        ],
                        'ReturnValues'              => 'ALL_NEW',
                        'TableName'                 => 'User',
                        'UpdateExpression'          => 'SET #K = :k, #F = :f',
                    ]);

                    return $response;
                } else {
                    return $response->withStatus(500); // We could not index the image
                }
            } else {
                return $response->withStatus(500); // We could not store the data
            }
        } else {
            return $response->withStatus(401); // TODO: Check this is correct
        }
    }

    public function entry(Request $request, Response $response, $args): Response
    {
        $user = $request->getAttribute('user');

        if (!isset($user)) {
            $database = $this->container->get('dynamodb');

            if ($databaseResult != null) {
                $body = $request->getBody();

                // TODO: 3. Remove any instance of face id (if any)
                $imaging = $this->container->get('rekognition');

                // 4. Add the image to the collection (specified by profile)
                $imagingResult = $imaging->searchFacesByImage([
                    'CollectionId' => $user->Item->CollectionId['S'], // TODO: Check this!
                    'Image'        => [
                        'Bytes' => $body,
                    ],
                ]);

                $faces = [];

                // 5. Find the information of all of the users that were in the photo
                foreach ($imagingResult->FaceMatches as $match) {
                    if ($user->Item->FaceId == $match->Face->FaceId) {
                        continue;
                    }

                    $matchResult = $database->query([
                        'TableName'                 => 'Visitor',
                        'ExpressionAttributeValues' => [
                            ':fi' => ['S' => $match->Face->FaceId],
                        ],
                        'KeyConditionExpression'    => 'FaceId = :fi',
                    ]);

                    if ($matchResult != null) {
                        array_push($faces, [
                            'Name'      => ['S' => $matchResult->Item->Name],
                            'VisitorId' => ['S' => $matchResult->Item->Id],
                        ]);
                    }
                }

                // 6. Insert the data into the datbase
                $insertResult = $database->putItem([
                    'TableName'    => 'Diary',
                    'Item'         => [
                        'Date'   => ['S' => date('Y-m-d')],
                        'UserId' => ['S' => $user->Item->Id],
                        'Faces'  => ['M' => $faces],
                    ],
                    'ReturnValues' => 'ALL_NEW',
                ]);

                if ($insertResult != null) {
                    return $response;
                } else {
                    return $response->withStatus(500); // Could not insert into the diary
                }
            }
        } else {
            return $response->withStatus(401); // TODO: Check this is correct
        }
    }
}
