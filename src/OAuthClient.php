<?php

namespace Haodai\UCenter\OAuth;

use Haodai\UCenter\OAuth\Constant\OAuthCode;
use Haodai\UCenter\OAuth\Constant\OAuthConstant;
use Haodai\UCenter\OAuth\Contracts\OAuthApiInterface;
use Haodai\UCenter\OAuth\Exception\OAuthException;
use Haodai\UCenter\OAuth\Utils\OAuthConfig;
use Haodai\UCenter\OAuth\Utils\OAuthTool;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * OAuth 客户端
 * Class OAuthClient
 * @package Haodai\UCenter\OAuth
 */
class OAuthClient
{
    /**
     * @var OAuthApiInterface
     */
    protected $oauthApi;

    /**
     * Client constructor.
     * @param OAuthApiInterface $oauthApi
     */
    public function __construct(OAuthApiInterface $oauthApi)
    {
        $this->oauthApi = $oauthApi;
    }

    /**
     * state session名称
     * @return string
     */
    protected function getStateSessionName()
    {
        return OAuthConfig::sessionName() . '.state';
    }

    /**
     * 随机生成16位字符串作为OAuth state参数
     * @return string
     */
    protected function generateState()
    {
        $state = Str::random(16);
        Session::put($this->getStateSessionName(), $state);
        return $state;
    }

    /**
     * 验证服务端返回的state
     * @param $state
     * @return bool
     */
    protected function validateState($state)
    {
        return $state == Session::pull($this->getStateSessionName(), '');
    }

    /**
     * OAuth 登录地址
     * @return string
     */
    public function getLoginUrl()
    {
        $params = [
            'response_type' => 'code',
            'client_id' => OAuthConfig::clientId(),
            'redirect_uri' => OAuthConfig::redirectUri(),
            'scope' => OAuthConfig::scope(),
            'approve' => 1,
            'state' => $this->generateState()
        ];
        return OAuthTool::getApiUrl(OAuthConstant::API_PATH_LOGIN, $params);
    }

    /**
     * OAuth 退出登录地址
     * @return string
     */
    public function getLogoutUrl()
    {
        $params = [
            'client_id' => OAuthConfig::clientId(),
            'redirectTo' => $this->getLoginUrl(),
        ];
        return OAuthTool::getApiUrl(OAuthConstant::API_PATH_LOGOUT, $params, true);
    }

    /**
     * token session 名称
     * @return string
     */
    protected function getTokenSessionName()
    {
        return OAuthConfig::sessionName() . '.token';
    }

    /**
     * token session 存储
     * @param array $token
     */
    protected function setTokenSession(array $token)
    {
        if (isset($token['expires_in'])) {
            $token['expires'] = time() + $token['expires_in'];
        }
        Session::put($this->getTokenSessionName(), $token);
    }

    /**
     * token session 获取
     * @return mixed
     */
    protected function getTokenSession()
    {
        return Session::get($this->getTokenSessionName(), []);
    }

    /**
     * 根据code或者从session中获取access_token
     * @param string $code
     * @return mixed|string
     */
    public function getAccessToken($code = '')
    {
        if ($code) {
            $token = $this->oauthApi->getAccessToken($code);
            $this->setTokenSession($token);
        } else {
            $token = $this->getTokenSession();
            if (isset($token['expires'])) {
                if ($token['expires'] <= time()) {
                    $token = $this->oauthApi->getRefreshToken($token['refresh_token']);
                }
            } else {
                $token = [];
            }
        }
        return isset($token['access_token']) ? $token['access_token'] : '';
    }

    /**
     * 获取当前oauth登录的用户信息
     * @param string $accessToken
     * @return array|mixed
     */
    public function getUserInfo($accessToken = '')
    {
        if (!$accessToken) {
            $accessToken = $this->getAccessToken();
        }
        return $accessToken ? $this->oauthApi->getUserInfo($accessToken) : [];
    }

    /**
     * OAuth登录回调，获取用户信息
     */
    public function getLoginUserInfo()
    {
        $code = Input::get('code', '');
        $state = Input::get('state', '');

        if (empty($state) || !$this->validateState($state)) {
            $message = "OAuth callback input[state:$state] illegal";
            throw new OAuthException($message, OAuthCode::OAUTH_CALLBACK_INPUT_ILLEGAL);
        }

        if (empty($code)) {
            $message = "OAuth callback input[code:$code] illegal";
            throw new OAuthException($message, OAuthCode::OAUTH_CALLBACK_INPUT_ILLEGAL);
        }

        $userInfo = $this->getUserInfo($this->getAccessToken($code));

        return $userInfo;
    }

}