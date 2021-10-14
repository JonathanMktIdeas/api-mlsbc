<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EventService;
use Exception;

class EventController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(EventService $service, Request $request)
    {
        parent::__construct($service, $request);
    }

    public function create()
    {
        $this->validate($this->request, [
            "title"   => "required|string",
            "start"   => "required|date",
            "end"     => "date",
            "content" => "required|string",
            "all_day" => "boolean"
        ]);

        try {
            $model = $this->service->create();
            return response()->json([
                'data' => $model,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function list()
    {
        try {
            $model = $this->service->list();
            return response()->json([
                'data' => $model,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function deleteEvent(int $id)
    {
        try {
            $model = $this->service->deleteEvent($id);
            return response()->json([
                'data' => $model,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }

    public function updateEvent(int $id)
    {
        $this->validate($this->request, [
            "title"   => "required|string",
            "start"   => "required|date",
            "end"     => "date",
            "content" => "required|string",
            "all_day" => "boolean"
        ]);

        try {
            $model = $this->service->updateEvent($id);
            return response()->json([
                'data' => $model,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        }
    }
}
