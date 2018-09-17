<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\Api\SocialAuthorizationRequest;
use App\Http\Requests\Api\AuthorizationRequest;
use Auth;

use Zend\Diactoros\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\AuthorizationServer;

use App\Traits\PassportToken;

use App\Http\Requests\Api\WeappAuthorizationRequest;
/**
 *  授权处理
 */
class AuthorizationsController extends Controller
{
    use PassportToken;

    //第三方登录 , $type:第三方类型，如weixin
    public function socialStore($type, SocialAuthorizationRequest $request)
    {
        if( !in_array($type,['weixin'])){
            return $this->response->errorBadRequest();
        }

        $driver = \Socialite::driver($type);

        try{
            if( $code = $request->code){
                $response = $driver->getAccessTokenResponse($code);
                $token = array_get($response, 'access_token');
            }else{
                $token = $request->access_token;
                if ($type == 'weixin') {
                    $driver->setOpenId($request->openid);
                }
            }
            $oauthUser = $driver->userFromToken($token);

        }catch(\Exception $e){
            return $this->response->errorUnauthorized('参数错误，未获取用户信息');
        }

        switch($type){
            case 'weixin':
                $unionid = $oauthUser->offsetExists('unionid') ? $oauthUser->offsetGet('unionid') : null;

                if( $unionid ){
                    $user = User::where('weixin_unionid',$unionid)->first();
                }else{
                    $user = User::where('weixin_openid',$oauthUser->getId())->first();
                }

                 // 没有用户，默认创建一个用户
                if( !$user ){
                    $user = User::create([
                        'name' => $oauthUser->getNickname(),
                        'avatar' => $oauthUser->getAvatar(),
                        'weixin_openid' => $oauthUser->getId(),
                        'weixin_unionid' => $unionid,
                    ]);
                }
                break;
        }

        if( env('IS_PASSPORT') ){
            $result = $this->getBearerTokenByUser($user, '1', false);
            return $this->response->array($result)->setStatusCode(201);
        }else{
            // 模型生成token
            $token = Auth::guard('api')->fromUser($user);
            return $this->respondWithToken($token)->setStatusCode(201);
        }
    }

    // 登录
    public function store(AuthorizationRequest $originRequest, AuthorizationServer $server, ServerRequestInterface $serverRequest)
    {
        if( env('IS_PASSPORT') ){
            try {
                return $server->respondToAccessTokenRequest($serverRequest, new Psr7Response)->withStatus(201);
             } catch(OAuthServerException $e) {
                 return $this->response->errorUnauthorized($e->getMessage());
             }
        }
        else{
            $request = $originRequest;
            $username = $request->username;

            // 判断是email登录还是phone登录
            filter_var($username , FILTER_VALIDATE_EMAIL ) ?
                $credentials['email'] = $username :
                $credentials['phone'] = $username ;
            
            $credentials['password'] = $request->password;
    
            if( !$token = \Auth::guard('api')->attempt($credentials) ){
                return $this->response->errorUnauthorized(trans('auth.failed'));
            }
    
            return $this->respondWithToken($token)->setStatusCode(201);
        }

    }

    protected function respondWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => \Auth::guard('api')->factory()->getTTL() * 60  //过期时间 单位是秒
        ]);
    }

    // 刷新token
    public function update(AuthorizationServer $server, ServerRequestInterface $serverRequest)
    {
        if( env('IS_PASSPORT') ){
            try {
                return $server->respondToAccessTokenRequest($serverRequest, new Psr7Response);
             } catch(OAuthServerException $e) {
                 return $this->response->errorUnauthorized($e->getMessage());
             }
        }else{
            $token = Auth::guard('api')->refresh();
            return $this->respondWithToken($token);
        }

    }

    // 删除token
    public function destroy()
    {
        if( env('IS_PASSPORT') ){
            $this->user()->token()->revoke();
            return $this->response->noContent();
        }else{
            Auth::guard('api')->logout();
            return $this->response->noContent();    
        }
    }

    // 小程序登录
    public function weappStore(WeappAuthorizationRequest $request)
    {
        // 小程序调用 wx.login() 接口获取临时登录凭证（code）
        $code = $request->code;

        // 根据 code 获取微信 openid 和 session_key
        $miniProgram = \EasyWeChat::miniProgram();
        $data = $miniProgram->auth->session($code);
        // data:{
        //     "session_key": "NdID/PfMZMH3emurxykSIQ==",
        //     "openid": "o9r7M4iGh54Mfbbf6cXzDcqRkFiM"
        // }
        
        // 如果结果错误，说明 code 已过期或不正确，返回 401 错误
        if(isset($data['errcode'])){
            return $this->response->errorUnauthorized('code 不正确');
        }

        $user = User::where('weapp_openid',$data['openid'])->first();
        $attributes['weixin_session_key'] = $data['session_key'];
        
        // 未找到对应用户则需要提交用户名密码进行用户绑定
        if(!$user){
            // 如果未提交用户名密码，403 错误提示
            if(!$request->username){
                return $this->response->errorForbidden('用户不存在');
            }
            $username = $request->username;
            
            // 用户名可以是邮箱或电话
            filter_var($username ,FILTER_VALIDATE_EMAIL)?
                $credentials['email'] = $username :
                $credentials['phone'] = $username;
            
            $credentials['password'] = $request->password;

            // 验证用户名和密码是否正确
            if(!Auth::guard('api')->once($credentials)){
                return $this->response->errorUnauthorized('用户名或密码错误');
            }

            // 获取对应的用户
            $user = Auth::guard('api')->getUser();
            $attributes['weapp_openid'] = $data['openid'];
        }
        // 更新用户数据
        $user->update($attributes);

        // 为对应用户创建 JWT
        $token = Auth::guard('api')->fromUser($user);
        return $this->respondWithToken($token)->setStatusCode(201);
    }
}
