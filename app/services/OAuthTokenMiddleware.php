<?php

namespace App\Services;

use App\Repositories\AccessTokenRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\AuthorizationValidators\BearerTokenValidator;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use Phalcon\Container;
use Phalcon\Http\Response;
use Sid\Phalcon\AuthMiddleware\MiddlewareInterface;

/**
 * Class OAuthTokenMiddleware
 * @package App\Services
 */
class OAuthTokenMiddleware implements MiddlewareInterface
{
    private $response = null;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function authenticate(): bool
    {
        $accessTokenRepository = new AccessTokenRepository();
        $bearerTokenValidator = new BearerTokenValidator($accessTokenRepository);
        $bearerTokenValidator->setPublicKey(new CryptKey($_ENV['PUBLIC_KEY_PATH'], null, false));

        try {

            $bearerTokenValidator->validateAuthorization(ServerRequest::fromGlobals());

            return true;

        } catch (OAuthServerException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw $exception;
        }

//        $response = $this->httpRequest->request('POST', '/api/v1/oauth/token/validate', [
//            'headers' => [
//                'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiJ0ZXN0IiwianRpIjoiOGNkZTU2NWEzNjdlNmZmZDkwODYwMzIwZjU1MzJiMzMzMTYzZDZmMjIzZjE0YjNlMGQ0ODcxNGFmNjhhZjBiOGI1NGVjNjA5MjE3MzhhODYiLCJpYXQiOjE1OTcxNzcyMzYsIm5iZiI6MTU5NzE3NzIzNiwiZXhwIjoxNTk3MTgwODM2LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.i7XhW9jG7ns0j3OcpkIXIk0QuFHNWKWnjftSmlMBhu9K1ldBcD60nppCAU_herXUuOUxXGh6zCtxdQKw68BaKwA1BXH_ckMVpId6bNqmn-S6akBdTkRyfmsGrYxQ3GkYU1_jZ2UrHXAlho1Hwjz_H8A9nz4aw_lupDzdSlyP6aINBRDhPGXOno5NZv2-1Kxr9dBCddulRJSpUPb_M4ZIzwxNBiwyn9JngWy7ZiBWMUeCjcGnmhG0v5cfCmrdel_Jv6LitNm9MXqEX6VzBX17H9BWHyHfMQXwg1mdVg7ik-yHI8A2ylPHIyFHpj5f-M_8chKzT7X_gKs-3DqtB77vXw'
//            ]
//        ]);
//
//        echo 'test';

        return true;
    }
}