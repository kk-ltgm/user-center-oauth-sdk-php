<?php

namespace Haodai\UCenter\OAuth\Constant;

class OAuthCode
{
    /**
     * API请求发生异常
     */
    const API_REQUEST_FAILED = 1001;

    /**
     * API响应内容解析失败
     */
    const API_RESPONSE_PARSE_FAILED = 1002;

    /**
     * API响应内容状态成功
     */
    const API_STATUS_SUCCESS = 2000;

    /**
     * API响应内容状态失败
     */
    const API_STATUS_FAILED = 4001;

    /**
     * OAUTH 回调参数校验失败
     */
    const OAUTH_CALLBACK_INPUT_ILLEGAL = 5001;

    /**
     * 参数签名无效
     */
    const OAUTH_INVALID_SIGN = 10001;


}

