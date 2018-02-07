<?php

namespace Haodai\UCenter\OAuth\Contracts;

/**
 * OAuth Http 请求
 * Interface OAuthHttpInterface
 * @package Haodai\UCenter\OAuth\Contractss
 */
interface OAuthHttpInterface
{

    /**
     * HTTP GET
     * @param string $url
     * @return array
     */
    public function get($url);

    /**
     * HTTP POST
     * @param string $url
     * @param array $data
     * @return array
     */
    public function post($url, $data = []);

}