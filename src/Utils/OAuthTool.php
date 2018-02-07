<?php

namespace Haodai\UCenter\OAuth\Utils;


class OAuthTool
{
    /**
     * OAuth Api地址
     * @param $apiPath
     * @param array $params GET参数
     * @param boolean $sign sign签名，如果需要，params参数不能为空
     * @return string
     */
    public static function getApiUrl($apiPath, array $params = [], $sign = false)
    {
        $url = rtrim(OAuthConfig::host(), '/') . $apiPath;
        if ($sign) {
            $params['ts'] = time();
            $params['sign'] = ParameterSign::make($params, OAuthConfig::clientSecret(), $params['ts']);
        }
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }
}