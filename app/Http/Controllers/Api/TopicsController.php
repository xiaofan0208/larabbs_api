<?php

namespace App\Http\Controllers\Api;

use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Requests\Api\TopicRequest;
use App\Transformers\TopicTransformer;

class TopicsController extends Controller
{
    // 发布话题
    public function store(TopicRequest $request ,Topic $topic )
    {
        $topic->fill($request->all());
        $topic->user_id = $this->user()->id;
        $topic->save();

        return $this->response->item($topic , new TopicTransformer() )
                ->setStatusCode(201) ;
    }
}
