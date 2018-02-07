<?php

namespace Haodai\UCenter\OAuth\Contracts;

interface OAuthApiInterface
{
    /**
     * 获取access token
     * @param string $code
     * @return array
     */
    public function getAccessToken($code);

    /**
     * 获取refresh token
     * @param string $refreshToken
     * @return array
     */
    public function getRefreshToken($refreshToken);

    /**
     * 获取用户信息
     * @param string $accessToken
     * @return array
     */
    public function getUserInfo($accessToken);
}