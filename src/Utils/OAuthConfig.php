<?php

namespace Haodai\UCenter\OAuth\Utils;

use Illuminate\Support\Facades\Config;

class OAuthConfig
{

    public static function item($key = null)
    {
        return $key ? Config::get('oauth.' . $key) : Config::get('oauth');
    }

    public static function __callStatic($name, $args)
    {
        // 驼峰命名转下划线配置名
        $key = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $name));
        return self::item($key);
    }
}
