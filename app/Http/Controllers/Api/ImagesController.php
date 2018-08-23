<?php

namespace App\Http\Controllers\Api;

use App\Models\Image;
use Illuminate\Http\Request;
use App\Http\Requests\Api\ImageRequest;
use App\Handlers\ImageUploadHandler;
use App\Transformers\ImageTransformer;

class ImagesController extends Controller
{
    // 更新 图片资源
    public function store(ImageRequest $request,ImageUploadHandler $uploader, Image $image)
    {
        $user = $this->user();

        $size = $request->type == 'avatar' ? 362 : 1024;
        $result = $uploader->save($request->image , str_plural($request->type) , $user->id,$size  );

        $image->path = $result['path'];
        $image->type = $request->type;
        $image->user_id = $user->id;
        $image->save();

        return $this->response->item($image , new ImageTransformer())
                    ->setStatusCode(201);
    }
}
