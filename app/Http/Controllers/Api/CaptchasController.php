<?php

namespace App\Http\Controllers\Api;

use Gregwar\Captcha\CaptchaBuilder;
use App\Http\Requests\Api\CaptchaRequest;
use Illuminate\Http\Request;

// 图片验证码
class CaptchasController extends Controller
{
    public function store(CaptchaRequest $request,CaptchaBuilder $captchaBuilder)
    {
        $key = 'captcha-'.str_random(15);
        $phone = $request->phone;

        $captcha = $captchaBuilder->build(); //创建验证码图片
        $expiredAt = now()->addMinutes(2);
        \Cache::put($key , ['phone' => $phone , 'code' => $captcha->getPhrase() ] , $expiredAt);
        
        $result = [
            'captcha_key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
            'captcha_image_content' => $captcha->inline() //inline 方法获取的 base64 图片验证码
        ];

        return $this->response->array($result)->setStatusCode(201);
    }

}
