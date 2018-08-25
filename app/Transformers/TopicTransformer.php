<?php

namespace App\Transformers;

use App\Models\Topic;
use League\Fractal\TransformerAbstract;

// 话题数组
class TopicTransformer  extends TransformerAbstract
{
    /*  额外可以获取的资源
        availableIncludes 中的每一个参数都对应一个具体的方法，
        方法命名规则为 include + user 、 include + category 驼峰命名。
    */
    protected $availableIncludes = ['user', 'category'];

    public function transform(Topic $topic)
    {
        return [
            'id' => $topic->id,
            'title' => $topic->title,
            'body' => $topic->body,
            'user_id' => (int) $topic->user_id,
            'category_id' => (int) $topic->category_id,
            'reply_count' => (int) $topic->reply_count,
            'view_count' => (int) $topic->view_count,
            'last_reply_user_id' => (int) $topic->last_reply_user_id,
            'excerpt' => $topic->excerpt,
            'slug' => $topic->slug,
            'created_at' => $topic->created_at->toDateTimeString(),
            'updated_at' => $topic->updated_at->toDateTimeString(),
        ];
    }

    // 如果在请求中包含 include=user，则TopicTransformer 自动调用 includeUser 方法。
    public function includeUser(Topic $topic )
    {
        return $this->item($topic->user , new UserTransformer() );
    }

    public function includeCategory(Topic $topic)
    {
        return $this->item($topic->category, new CategoryTransformer());
    }
}