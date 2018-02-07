<?php

namespace Haodai\UCenter\OAuth\Api;

use Haodai\UCenter\OAuth\Constant\OAuthConstant;
use Haodai\UCenter\OAuth\Contracts\OAuthApiInterface;
use Haodai\UCenter\OAuth\Contracts\OAuthHttpInterface;
use Haodai\UCenter\OAuth\Utils\OAuthConfig;
use Haodai\UCenter\OAuth\Utils\OAuthTool;

/**
 * Class OAuthApi
 * @package Haodai\UCenter\OAuth\Api
 */
class OAuthApi implements OAuthApiInterface
{

    /**
     * @var OAuthHttpInterface
     */
    protected $http;

    /**
     * OAuthApi constructor.
     * @param OAuthHttpInterface $http
     */
    public function __construct(OAuthHttpInterface $http)
    {
        $this->http = $http;
    }

    /**
     * 获取access token
     * @param string $code
     * @return array
     */
    public function getAccessToken($code)
    {
        $url = OAuthTool::getApiUrl(OAuthConstant::API_PATH_ACCESS_TOKEN);
        $data = [
            'grant_type' => 'authorization_code',
            'client_id' => OAuthConfig::clientId(),
            'client_secret' => OAuthConfig::clientSecret(),
            'redirect_uri' => OAuthConfig::redirectUri(),
            'code' => $code
        ];
        return $this->http->post($url, $data);
    }

    /**
     * 获取refresh token
     * @param string $refreshToken
     * @return array
     */
    public function getRefreshToken($refreshToken)
    {
        $url = OAuthTool::getApiUrl(OAuthConstant::API_PATH_ACCESS_TOKEN);
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];
        return $this->http->post($url, $data);
    }

    /**
     * 获取用户信息
     * @param string $accessToken
     * @return array
     */
    public function getUserInfo($accessToken)
    {
        $params = [
            'access_token' => $accessToken
        ];
        $url = OAuthTool::getApiUrl(OAuthConstant::API_PATH_USER_INFO, $params, true);
        $result = $this->http->get($url);
        return $result['data'];
    }

}
