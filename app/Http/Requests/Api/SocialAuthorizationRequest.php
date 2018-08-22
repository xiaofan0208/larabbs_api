<?php

namespace App\Http\Requests\Api;

use Dingo\Api\Http\FormRequest;

// 第三方验证
class SocialAuthorizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // code 和 access_token 两者必有一个
        $rules = [
            'code' => 'required_without:access_token|string',
            'access_token' => 'required_without:code|string',
        ];

        // 如果微信登录，而且没有授权码，则需要 openid
        if( $this->social_type == 'weixin' &&!$this->code){
            $rules['openid']  = 'required|string';
        }

        return $rules;
    }
}
