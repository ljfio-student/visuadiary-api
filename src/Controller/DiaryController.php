<?php

namespace Site\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;

class DiaryController extends Controller
{
    /**
     * @api {get} /diary/ Get the diary
     * @apiName GetDiary
     * @apiGroup Diary
     *
     * @apiSuccess {Object[]} visits List of visits to person
     * @apiSuccess {Number} visits.id The unique ID for the visit
     * @apiSuccess {Date} visits.date Date of the visit
     * @apiSuccess {Number} visits.people Count of how many people were there
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "visits": [
     *         "id": 1234,
     *         "date": "2017-06-12T23:00:00Z",
     *         "people": 1
     *       ]
     *     }
     *
     */
    public function list(Request $request, Response $response, $args): Response
    {
        $user = $request->getAttribute('user');

        if (!isset($user)) {
            return $response->withStatus(401);
        }

        $database = $this->container->get('database');

        $visits = $database->table('diary')
            ->where([
                ['user_id', '=', $user->id],
            ])
            ->join('diary_visitor', 'diary_visitor.diary_id', '=', 'diary.id')
            ->select('diary.id', 'diary.date', 'count(diary_visitor.id) as people')
            ->groupBy('diary.id', 'diary.date')
            ->get();

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withBody([
                'visits' => json_encode($visits),
            ]);
    }

    /**
     * @api {get} /diary/:id Get speciifc diary information
     * @apiName GetVisit
     * @apiGroup Diary
     *
     * @apiSuccess {Object[]} people People that turned up to the event
     * @apiSuccess {Number} people.id The unique ID for the person
     * @apiSuccess {String} people.name Name of the person who arrived
     * @apiSuccess {String} people.image The URL for the image
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "date": "2017-06-12T23:00:00Z",
     *       "image": "http://visuadiary.com/image.jpg",
     *       "people": [
     *         "id": 1234,
     *         "name": "Luke Fisher"
     *       ]
     *     }
     *
     */
    public function get(Request $request, Response $response, $args): Response
    {
        $user = $request->getAttribute('user');

        if (!isset($user)) {
            return $response->withStatus(401); // Couldn't authenticate the current suer
        }

        $database = $this->contaienr->get('database');

        $visit = $database->table('diary')
            ->where([
                ['id', '=', $args['id']],
                ['user_id', '=', $user->id]
            ])
            ->select('date', 'aws_s3_key as ')
            ->first();

        if ($vist == null) {
            return $response->withStatus(404); // Couldn't find the diary date
        }

        $people = $database->table('diary_visitor')
            ->where([
                ['diary_id', '=', $visit->id]]
            ])
            ->join('visitor', '')
            ->get();
    }
}
