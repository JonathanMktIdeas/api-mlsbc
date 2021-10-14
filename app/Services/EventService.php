<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Agency;
use App\Services\OpenPayService;
use Illuminate\Support\Facades\Hash;
use Exception;
use DB;

class EventService extends BaseService
{
    private $request;
    public function __construct(Event $model, Request $request)
    {
        $this->model = $model;
        $this->request = $request;
    }

    public function create()
    {
        $params = [
            'title'   => $this->request->get('title'),
            'content' => $this->request->get('content'),
            'start'   => $this->request->get('start'),
            'all_day' => $this->request->get('all_day'),
        ];

        if ($this->request->get('end') && !$this->request->get('all_day'))
        {
            $params['end'] = $this->request->get('end');
        }

        return $this->model->create($params);
    }

    public function list()
    {
        $start = date('Y-m-01');
        $end   = date("Y-m-t", strtotime($start));

        $data = $this->model
            ->whereDate('start', '>=', $start)
            ->where(function($q) use ($end) {
                $q->where('end', null)
                    ->orWhereDate('end', '<=', $end);
            })
            ->get();

        return $data;
    }

    public function deleteEvent(int $id)
    {
        $model = $this->model->find($id);

        if (!$model)
        {
            throw new Exception("Event not found", 1);
        }

        return $model->delete();
    }

    public function updateEvent(int $id)
    {
        $model = $this->model->find($id);

        if (!$model)
        {
            throw new Exception("Event not found", 1);
        }

        if ($this->request->get('end') && !$this->request->get('all_day'))
        {
            $model->end = $this->request->get('end');
        }

        if ($this->request->get('title')) $model->title = $this->request->get('title');
        if ($this->request->get('content')) $model->content = $this->request->get('content');
        if ($this->request->get('start')) $model->start = $this->request->get('start');
        if ($this->request->get('all_day')) $model->all_day = $this->request->get('all_day');

        return $model->save();
    }
}
