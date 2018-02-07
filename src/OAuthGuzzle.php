<?php

namespace Haodai\UCenter\OAuth;

use GuzzleHttp\Client;
use Haodai\UCenter\OAuth\Constant\OAuthCode;
use Haodai\UCenter\OAuth\Contracts\OAuthHttpInterface;
use Haodai\UCenter\OAuth\Exception\OAuthException;
use Psr\Http\Message\ResponseInterface;

class OAuthGuzzle implements OAuthHttpInterface
{

    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function get($url)
    {
        return $this->request('GET', $url);
    }

    public function post($url, $data = [])
    {
        $options = [];
        if (!empty($data)) {
            $options['form_params'] = $data;
        }
        return $this->request('POST', $url, $options);
    }

    public function request($method, $uri, array $options = [])
    {
        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (\Exception $e) {
            throw new OAuthException($e->getMessage(), OAuthCode::API_REQUEST_FAILED, $e);
        }
        return $this->parseResponse($response);
    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     */
    protected function parseResponse(ResponseInterface $response)
    {
        $contents = $response->getBody()->getContents();

        $result = json_decode($contents, true);

        // 验证json 解析
        if (JSON_ERROR_NONE !== json_last_error() || !is_array($result)) {
            $message = sprintf("json_decode error: %s, json_data: %s", json_last_error_msg(), (string) $contents);
            throw new OAuthException($message, OAuthCode::API_RESPONSE_PARSE_FAILED);
        }

        if (isset($result['status']) && OAuthCode::API_STATUS_SUCCESS != $result['status']) {
            $message = sprintf('api status is failed: %s', $result['status']);
            throw new OAuthException($message, OAuthCode::API_STATUS_FAILED);
        }
        return $result;
    }

}