<?php

namespace Haodai\UCenter\OAuth\Utils;

/**
 * 参数签名
 * Class ParameterSign
 * @package App\Support
 */
class ParameterSign
{
    private static $expire = 3;

    public static function make($data, $secret, $ts)
    {
        $strToEncrypt = self::buildStr($data, $secret, $ts);

        $encrypter = new EncryptUtil($secret);
        return $encrypter->encrypt($strToEncrypt);
    }

    public static function check($data, $secret)
    {
        if (!$secret || !(isset($data['ts'])) || !(isset($data['sign']))) {
            return false;
        }

        $ts = $data['ts'];
        // 过期
        if ((time() - $ts) > self::$expire) {
            return false;
        }

        $originalStr = self::buildStr($data, $secret, $ts);

        $decrypter = new EncryptUtil($secret);
        $str = $decrypter->decrypt($data['sign']);

        return $str == $originalStr;
    }

    private static function buildStr($data, $secret, $ts)
    {
        unset($data['sign']);
        unset($data['ts']);
        $str = '';  //待签名字符串
        //先将参数以其参数名的字典序升序进行排序
        ksort($data);
        //遍历排序后的参数数组中的每一个key/value对
        foreach ($data as $k => $v) {
            //为key/value对生成一个key=value格式的字符串，并拼接到待签名字符串后面
            $str .= "$k=$v";
        }
        //将签名密钥拼接到签名字符串最后面
        $str .= $secret;
        $strToEncrypt = md5($str . $ts);

        return $strToEncrypt;
    }
}