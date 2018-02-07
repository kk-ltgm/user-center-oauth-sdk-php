<?php

namespace Haodai\UCenter\OAuth\Utils;

/**
 * 签名加密解密
 * Class EncryptUtil
 * @package Haodai\UCenter\Oauth\Utils
 */
class EncryptUtil
{
    /**
     * The algorithm used for encryption.
     *
     * @var string
     */
    protected $cipher;

    /**
     * The encryption key.
     *
     * @var string
     */
    protected $key;

    /**
     * Create a new encrypter instance.
     * @param $key
     * @param string $cipher
     *
     * @throws \RuntimeException
     */
    public function __construct($key, $cipher = 'AES-256-CBC')
    {
        $key = (string) $key;

        if (static::supported($key, $cipher)) {
            $this->key = $key;
            $this->cipher = $cipher;
        } else {
            throw new \RuntimeException('The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.');
        }
    }

    /**
     * Determine if the given key and cipher combination is valid.
     *
     * @param  string  $key
     * @param  string  $cipher
     * @return bool
     */
    public static function supported($key, $cipher)
    {
        $length = mb_strlen($key, '8bit');

        return ($cipher === 'AES-128-CBC' && $length === 16) || ($cipher === 'AES-256-CBC' && $length === 32);
    }

    /**
     * Encrypt the given value.
     *
     * @param  string  $value
     * @return string
     *
     * @throws \Exception
     */
    public function encrypt($value)
    {
        $iv = static::randomBytes($this->getIvSize());

        $value = \openssl_encrypt(serialize($value), $this->cipher, $this->key, 0, $iv);

        if ($value === false) {
            throw new \Exception('Could not encrypt the data.');
        }

        // Once we have the encrypted value we will go ahead base64_encode the input
        // vector and create the MAC for the encrypted value so we can verify its
        // authenticity. Then, we'll JSON encode the data in a "payload" array.
        $mac = $this->hash($iv = base64_encode($iv), $value);

        $json = json_encode(compact('iv', 'value', 'mac'));

        if (! is_string($json)) {
            throw new \Exception('Could not encrypt the data.');
        }

        return base64_encode($json);
    }

    /**
     * Decrypt the given value.
     *
     * @param  string  $payload
     * @return string
     *
     * @throws \Exception
     */
    public function decrypt($payload)
    {
        $payload = $this->getJsonPayload($payload);

        $iv = base64_decode($payload['iv']);

        $decrypted = \openssl_decrypt($payload['value'], $this->cipher, $this->key, 0, $iv);

        if ($decrypted === false) {
            throw new \Exception('Could not decrypt the data.');
        }

        return unserialize($decrypted);
    }

    /**
     * Get the IV size for the cipher.
     *
     * @return int
     */
    protected function getIvSize()
    {
        return 16;
    }

    /**
     * Create a MAC for the given value.
     *
     * @param  string  $iv
     * @param  string  $value
     * @return string
     */
    protected function hash($iv, $value)
    {
        return hash_hmac('sha256', $iv.$value, $this->key);
    }

    /**
     * Get the JSON array from the given payload.
     *
     * @param  string  $payload
     * @return array
     *
     * @throws \Exception
     */
    protected function getJsonPayload($payload)
    {
        $payload = json_decode(base64_decode($payload), true);

        // If the payload is not valid JSON or does not have the proper keys set we will
        // assume it is invalid and bail out of the routine since we will not be able
        // to decrypt the given value. We'll also check the MAC for this encryption.
        if (! $payload || $this->invalidPayload($payload)) {
            throw new \Exception('The payload is invalid.');
        }

        if (! $this->validMac($payload)) {
            throw new \Exception('The MAC is invalid.');
        }

        return $payload;
    }

    /**
     * Verify that the encryption payload is valid.
     *
     * @param  array|mixed  $data
     * @return bool
     */
    protected function invalidPayload($data)
    {
        return ! is_array($data) || ! isset($data['iv']) || ! isset($data['value']) || ! isset($data['mac']);
    }

    /**
     * Determine if the MAC for the given payload is valid.
     *
     * @param  array  $payload
     * @return bool
     *
     * @throws \RuntimeException
     */
    protected function validMac(array $payload)
    {
        $bytes = static::randomBytes(16);

        $calcMac = hash_hmac('sha256', $this->hash($payload['iv'], $payload['value']), $bytes, true);

        return static::equals(hash_hmac('sha256', $payload['mac'], $bytes, true), $calcMac);
    }

    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int  $length
     * @return string
     */
    public static function random($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = static::randomBytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    /**
     * Generate a more truly "random" bytes.
     *
     * @param  int  $length
     * @return string
     */
    public static function randomBytes($length = 16)
    {
        if(!isset($length) || intval($length) <= 8 ){
            $length = 32;
        }

        return \openssl_random_pseudo_bytes($length);
    }

    /**
     * Compares two strings using a constant-time algorithm.
     *
     * Note: This method will leak length information.
     *
     * Note: Adapted from Symfony\Component\Security\Core\Util\StringUtils.
     *
     * @param  string  $knownString
     * @param  string  $userInput
     * @return bool
     */
    public static function equals($knownString, $userInput)
    {
        if (! is_string($knownString)) {
            $knownString = (string) $knownString;
        }

        if (! is_string($userInput)) {
            $userInput = (string) $userInput;
        }

        if (function_exists('hash_equals')) {
            return hash_equals($knownString, $userInput);
        }

        $knownLength = mb_strlen($knownString, '8bit');

        if (mb_strlen($userInput, '8bit') !== $knownLength) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $knownLength; ++$i) {
            $result |= (ord($knownString[$i]) ^ ord($userInput[$i]));
        }

        return 0 === $result;
    }

}