<?php

namespace Haodai\UCenter\OAuth\Middleware;

use Closure;
use Haodai\UCenter\OAuth\Constant\OAuthCode;
use Illuminate\Http\JsonResponse;
use Haodai\UCenter\OAuth\Utils\ParameterSign;
use Illuminate\Support\Facades\Config;

class VerifySignature
{
    /**
     * Verify the oauth parameter signature
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $query = $request->query->all();
        unset($query['s'], $query['callback'], $query['_']);

        if (!ParameterSign::check($query, Config::get('oauth.client_secret'))) {
            return new JsonResponse([
                'message' => '签名验证失败',
                'status' => OAuthCode::OAUTH_INVALID_SIGN
            ], 403);
        }
        return $next($request);
    }
}
