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
            $database = $this->container->get('database');

            // 1. Check visitor exists and get collection/face id
            $databaseResult = $database->table('visitor')
                ->where([
                    ['id' => $args['id']],
                    ['user_id' => $args['user_id']],
                ])
                ->first();

            if ($databaseResult != null) {
                $body = $request->getBody();
                // TODO: Check that the content type is legit

                $storage = $this->container->get('s3');

                $key = uniqid("v_"); // Unique key name for object

                // 2. Upload the image into S3
                $storageResult = $storage->putObject([
                    'Body'   => $body,
                    'Key'    => $key,
                    'Bucket' => $this->container->get('settings')['aws']['bucket'],
                ]);

                if ($storageResult != null) {
                    // TODO: 3. Remove any instance of face id (if any)
                    $imaging = $this->container->get('rekognition');

                    // 4. Add the image to the collection (specified by profile)
                    $imagingResult = $imaging->indexFaces([
                        'CollectionId' => $user->aws_collection_id], // TODO: Check this!
                        'Image'        => [
                            'S3Object' => [
                                'Bucket' => $this->container->get('settings')['aws']['bucket'],
                                'Name'   => $key,
                            ],
                        ],
                    ]);

                    if ($imagingResult != null) {
                        // 5. Put face id on the visitor data
                        $database->table('visitor')
                            ->where([
                                ['id', '=', $args['id']],
                            ])
                            ->update([
                                'aws_s3_key' => $key,
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
                'Bucket' => $this->container->get('settings')['aws']['bucket'],
            ]);

            if ($storageResult != null) {
                // TODO: 3. Remove any instance of face id (if any)
                $imaging = $this->container->get('rekognition');

                // 4. Add the image to the collection (specified by profile)
                $imagingResult = $imaging->indexFaces([
                    'CollectionId' => $user->aws_collection_id, // TODO: Check this!
                    'Image'        => [
                        'S3Object' => [
                            'Bucket' => $this->container->get('settings')['aws']['bucket'],
                            'Name'   => $key,
                        ],
                    ],
                ]);

                if ($imagingResult != null) {
                    // 5. Put face id on the visitor data
                    $database->table('user')
                        ->where([
                            ['id' => $args['id']],
                        ])
                        ->update([
                            'aws_s3_key' => $key,
                            'aws_face_id' => $imagingResult->FaceRecords[0]->Face->FaceId,
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
            $database = $this->container->get('database');

            $body = $request->getBody();

            $storage = $this->container->get('s3');

            $key = uniqid("d_"); // Unique key name for object

            // 2. Upload the image into S3
            $storageResult = $storage->putObject([
                'Body'   => $body,
                'Key'    => $key,
                'Bucket' => $this->container->get('settings')['aws']['bucket'],
            ]);

            if ($storageResult != null) {
                // TODO: 3. Remove any instance of face id (if any)
                $imaging = $this->container->get('rekognition');

                // 4. Add the image to the collection (specified by profile)
                $imagingResult = $imaging->searchFacesByImage([
                    'CollectionId' => $user->aws_collection_id, // TODO: Check this!
                    'Image'        => [
                        'S3Object' => [
                            'Bucket' => $this->container->get('settings')['aws']['bucket'],
                            'Name'   => $key,
                        ],
                    ],
                ]);

                $faces = [];

                // 5. Find the information of all of the users that were in the photo
                foreach ($imagingResult->FaceMatches as $match) {
                    if ($user->aws_face_id == $match->Face->FaceId) {
                        continue;
                    }

                    $matchResult = $database->table('visitor')
                        ->where([
                            ['aws_face_id', '=', $match->Face->FaceId],
                        ])
                        ->first();

                    if ($matchResult != null) {
                        array_push($faces, [
                            'name'       => $matchResult->Item->Name,
                            'visitor_id' => $matchResult->Item->Id,
                        ]);
                    }
                }

                // 6. Insert the data into the datbase
                $diaryEntry = $database->table('diary')
                    ->insertGetId([
                        'date'    => date('Y-m-d'),
                        'user_id' => $user->id,
                    ]);

                // 7. Insert into the diart_visitor table
                if ($diaryEntry != null) {
                    foreach ($faces as $face) {
                        $database->table('diary_visitor')
                            ->insert([
                                [
                                    'diary_id' => $diaryEntry,
                                    'visitor_id' => $face['visitor_id'],
                                ]
                            ]);
                    }

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
