<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\Traits\ActingJWTUser;
use App\Models\Topic;

class TopicApiTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }


    use ActingJWTUser;
    protected $user;

    // setUp 方法会在测试开始之前执行，我们先创建一个用户，测试会以该用户的身份进行测试
    public function setUp()
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
    }

    // 测试发布话题
    public function testStoreTopic()
    {
        $data = ['category_id' => 1, 'body' => 'test body', 'title' => 'test title'];

        $response = $this->JWTActingAs($this->user)
            ->json('POST', '/api/topics', $data);

        $assertData = [
            'category_id' => 1,
            'user_id' => $this->user->id,
            'title' => 'test title',
            'body' => clean('test body', 'user_topic_body'),
        ];     

        $response->assertStatus(201)
            ->assertJsonFragment($assertData);
    }


    protected function makeTopic()
    {
        return factory(Topic::class)->create([
            'user_id' => $this->user->id,
            'category_id' => 1,
        ]);
    }
     // 测试修改话题
     public function testUpdateTopic()
     {
        $topic = $this->makeTopic();
        $editData = ['category_id' => 2, 'body' => 'edit body', 'title' => 'edit title'];

        $response = $this->JWTActingAs($this->user)
            ->json('PATCH', '/api/topics/'.$topic->id, $editData);
    
        $assertData= [
            'category_id' => 2,
            'user_id' => $this->user->id,
            'title' => 'edit title',
            'body' => clean('edit body', 'user_topic_body'),
        ];
    
        $response->assertStatus(200)
            ->assertJsonFragment($assertData);
     }

    // 测试查看话题
    public function testShowTopic()
    {
        $topic = $this->makeTopic();
        $response = $this->json('GET', '/api/topics/'.$topic->id);
    
        $assertData= [
            'category_id' => $topic->category_id,
            'user_id' => $topic->user_id,
            'title' => $topic->title,
            'body' => $topic->body,
        ];
    
        $response->assertStatus(200)
            ->assertJsonFragment($assertData);
    }

    // 话题列表
    public function testIndexTopic()
    {
        $response = $this->json('GET', '/api/topics');
    
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    // 测试删除话题
    public function testDeleteTopic()
    {
        $topic = $this->makeTopic();
        $response = $this->JWTActingAs($this->user)
            ->json('DELETE', '/api/topics/'.$topic->id);
        $response->assertStatus(204);

        $response = $this->json('GET', '/api/topics/'.$topic->id);
        $response->assertStatus(404);
    }

}
