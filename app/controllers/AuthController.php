<?php
declare(strict_types=1);

use App\Library\ResponseCodes;
use App\Library\Utils;
use App\Models\User;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;

/**
 * Class AuthController
 */
class AuthController extends \Phalcon\Mvc\Controller
{
    use Utils;

    /**
     * Get authorization code
     * @return mixed
     */
    public function authorizeAction()
    {
        $serverResponse = new \GuzzleHttp\Psr7\Response();

        /** @var \App\Services\Response $httpResponse */
        $httpResponse = $this->container->get('response');

        try {
            $request = ServerRequest::fromGlobals();

            /** @var \League\OAuth2\Server\AuthorizationServer $oauth2Server */
            $oauth2Server = $this->container->get('oauth2Server');

            // this is where the client gets validated
            //(e.g how facebook validates/verifies the Spotify Web client)
            $authRequest = $oauth2Server->validateAuthorizationRequest($request);

            // The auth request object can be serialized and saved into a user's session.
            // You will probably want to redirect the user at this point to a login endpoint.
            //(e.g the point where Facebook now requests for your username and password)

            // Once the user has logged in set the user on the AuthorizationRequest
            $authRequest->setUser(new User([
                'id' => '1'
            ])); // an instance of UserEntityInterface

            // Once the user has approved or denied the client update the status
            // (true = approved, false = denied)
            $authRequest->setAuthorizationApproved(true);

            // Return the HTTP redirect response
            $response =  $this->oauth2Server->completeAuthorizationRequest($authRequest, $serverResponse);

            $redirectUrl = $response->getHeaders()['Location'][0];
            //this redirect url should contain the the authorization code and the optional state parameter
            //redirect to this url and request for a token with the authorization code using the token endpoint

            $payload = [
                'token' => $redirectUrl
            ];

            $httpResponse->appendContent(json_encode($payload));
            $httpResponse->setStatusCode(200);

            return $httpResponse->send();

        } catch (OAuthServerException $exception) {
            $response = $exception->generateHttpResponse($serverResponse);
            $payload = $exception->getPayload();

            $httpResponse->appendContent($payload['message']);
            $httpResponse->setStatusCode($response->getStatusCode());

            return $httpResponse->send();

        } catch (\Exception $exception) {

            $httpResponse->appendContent($exception->getMessage());
            $httpResponse->setStatusCode($exception->getCode());

            return $httpResponse->send();
        }
    }
}